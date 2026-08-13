# ملحق عقد التصميم الفني والمعماري — الإصدار 1.7 (Amendment 3)
**المشروع:** منصة التجارة والمحفظة الرقمية Bagisto 2.4.x  
**الملف:** `WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.7_AMENDMENT_3.md`  
**الوثائق المرجعية:** [WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.7.md](file:///e:/HIGESTO%20NEW1/higest/higest101/WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.7.md) و [WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.7_AMENDMENT_2.md](file:///e:/HIGESTO%20NEW1/higest/higest101/WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.7_AMENDMENT_2.md)  
**الحالة:** ملحق ضبط وتوحيد المسؤوليات المحاسبية (Pre-Implementation Final Architectural Amendment)  
**تاريخ الإصدار:** 13 أغسطس 2026  

---

## 1. إثبات المسؤولية الحصرية لـ `WalletService` عن تعديل الأرصدة ومنع التحديث المزدوج

### 1.1. الدليل المصدري المثبت من كود `WalletService.php`:
في الملف `packages/Webkul/Wallet/src/Services/WalletService.php` (السطور 63-66):
```php
$wallet->increment('available_balance', $amount);
$wallet->increment('total_balance', $amount);
$this->assertWalletInvariant($wallet);
```
- **المبدأ المعماري الصارم (Single Responsibility Principle):**
  - خدمة المحفظة `WalletService` (أو دالتها المخصصة `creditPromotion`) هي **المسؤول الوحيد والمطلق** عن تعديل أعمدة الأرصدة (`promo_balance`, `available_balance`, `total_balance`) وقيد حركة الـ Ledger في جدول `wallet_transactions`.
  - **حظر قطعي:** يُمنع منعاً باتاً استدعاء `$lockedWallet->promo_balance += ...` أو `$lockedWallet->save()` للأرصدة داخل معالج العروض بعد استدعاء `WalletService::creditPromotion`، لأن ذلك يتسبب في **خلل التحديث المزدوج (Double-Increment Bug)** ومضاعفة الرصيد الممنوح.

---

## 2. الخوارزمية الذرية الحتمية والمحمية من التكرار (`applyGrantWithDebtSettlement`)

تتضمن الخوارزمية فحص الـ Idempotency في أول سطر، وفحص `backfill_status`، وقفل الديون، وتفويض تحديث الرصيد لـ `WalletService`:

```php
public function applyGrantWithDebtSettlement(
    WalletAccount $wallet, 
    WalletPromotion $promotion,
    string $grantAmountStr, 
    string $eventKey
): array {
    return DB::transaction(function () use ($wallet, $promotion, $grantAmountStr, $eventKey) {
        $lockedWallet = WalletAccount::lockForUpdate()->findOrFail($wallet->id);

        // 1. حظر الحسابات قيد التدقيق المحاسبي (Account Under Audit Guard)
        if ($lockedWallet->backfill_status === 'pending_review') {
            throw new \Webkul\Wallet\Exceptions\AccountUnderAuditException(
                "Account #{$lockedWallet->id} is under audit review and cannot receive promotional grants."
            );
        }

        // 2. فحص الـ Idempotency المسبق لمنع تكرار إنشاء Usages أو Grants
        $existingUsage = WalletPromotionUsage::where('promotion_id', $promotion->id)
            ->where('event_key', $eventKey)
            ->lockForUpdate()
            ->first();

        if ($existingUsage) {
            if ($existingUsage->status === 'approved') {
                $existingGrant = WalletPromotionGrant::where('usage_id', $existingUsage->id)->firstOrFail();
                return [
                    'grant'        => $existingGrant,
                    'settled'      => '0.0000',
                    'net_credited' => (string) $existingGrant->remaining_amount,
                    'is_idempotent'=> true
                ];
            }
            throw new \RuntimeException("Usage event #{$eventKey} exists in state [{$existingUsage->status}].");
        }

        // 3. قفل ديون العميل للتحقق من التسويات والمطابقة
        $activeDebts = WalletPromoDebt::where('customer_id', $lockedWallet->customer_id)
            ->where('remaining_debt_amount', '>', 0)
            ->orderBy('id', 'ASC')
            ->lockForUpdate()
            ->get();

        // 4. إنشاء سجل الاستخدام
        $usage = WalletPromotionUsage::create([
            'promotion_id'       => $promotion->id,
            'customer_id'        => $lockedWallet->customer_id,
            'event_key'          => $eventKey,
            'reward_amount'      => $grantAmountStr,
            'base_reward_amount' => $grantAmountStr,
            'currency_code'      => 'SAR',
            'exchange_rate'      => '1.0000',
            'status'             => 'approved',
            'promotion_snapshot' => $promotion->toJson(),
        ]);

        $remainingGrantToCredit = $grantAmountStr;
        $totalSettled = '0.0000';
        $settlementsToInsert = [];

        // 5. احتساب تسويات الديون المستحقة
        foreach ($activeDebts as $debt) {
            if (bccomp($remainingGrantToCredit, '0.0000', 4) <= 0) {
                break;
            }

            $debtRemainingStr = (string) $debt->remaining_debt_amount;
            $settlementAmount = (bccomp($debtRemainingStr, $remainingGrantToCredit, 4) <= 0)
                ? $debtRemainingStr
                : $remainingGrantToCredit;

            $newDebtRemaining = bcsub($debtRemainingStr, $settlementAmount, 4);
            $newDebtSettled = bcadd((string) $debt->settled_amount, $settlementAmount, 4);

            $debt->remaining_debt_amount = $newDebtRemaining;
            $debt->settled_amount = $newDebtSettled;
            $debt->status = (bccomp($newDebtRemaining, '0.0000', 4) === 0) ? 'settled' : 'partially_settled';
            if ($debt->status === 'settled') {
                $debt->settled_at = now();
            }
            $debt->save();

            // تجهيز بيانات التسوية (دون إنشاء حركة Ledger وهمية للتسوية غير النقدية)
            $settlementsToInsert[] = [
                'debt'              => $debt,
                'settlement_amount' => $settlementAmount,
            ];

            $totalSettled = bcadd($totalSettled, $settlementAmount, 4);
            $remainingGrantToCredit = bcsub($remainingGrantToCredit, $settlementAmount, 4);
        }

        // 6. إنشاء سجل الحصة (Grant) بقيمتها المتبقية والمستهلكة في التسوية
        $grantStatus = (bccomp($remainingGrantToCredit, '0.0000', 4) === 0) ? 'fully_consumed' : 'partially_consumed';
        if (bccomp($totalSettled, '0.0000', 4) === 0) {
            $grantStatus = 'active';
        }

        $grant = WalletPromotionGrant::create([
            'promotion_id'    => $promotion->id,
            'customer_id'     => $lockedWallet->customer_id,
            'wallet_id'       => $lockedWallet->id,
            'usage_id'        => $usage->id,
            'original_amount' => $grantAmountStr,
            'remaining_amount'=> $remainingGrantToCredit,
            'consumed_amount' => $totalSettled,
            'currency_code'   => 'SAR',
            'base_amount'     => $grantAmountStr,
            'status'          => $grantStatus,
            'reference_type'  => get_class($promotion),
            'reference_id'    => $promotion->id,
            'granted_at'      => now(),
            'expires_at'      => $promotion->grant_validity_days ? now()->addDays($promotion->grant_validity_days) : null,
        ]);

        // 7. حفظ سجلات تسويات الديون وربطها بالـ Grant المنشأ
        foreach ($settlementsToInsert as $item) {
            WalletPromoDebtSettlement::create([
                'debt_id'                => $item['debt']->id,
                'wallet_id'              => $lockedWallet->id,
                'customer_id'            => $lockedWallet->customer_id,
                'grant_id'               => $grant->id,
                'settlement_amount'      => $item['settlement_amount'],
                'base_settlement_amount' => $item['settlement_amount'],
                'currency_code'          => 'SAR',
                'wallet_transaction_id'  => null, // التسوية غير النقدية لا تنشئ حركة نقدية في الـ Ledger
                'event_key'              => "debt:{$item['debt']->id}:grant:{$grant->id}:settle",
            ]);
        }

        // 8. تحديث دين المحفظة المجمع مع تأكيد Invariant الديون
        if (bccomp($totalSettled, '0.0000', 4) > 0) {
            $lockedWallet->decrement('promo_debt', (float) $totalSettled);
        }

        // تأكيد Invariant الديون: wallet_accounts.promo_debt == sum(active remaining_debt_amount)
        $expectedTotalDebt = (string) WalletPromoDebt::where('customer_id', $lockedWallet->customer_id)
            ->where('remaining_debt_amount', '>', 0)
            ->sum('remaining_debt_amount');

        if (bccomp((string) $lockedWallet->promo_debt, $expectedTotalDebt, 4) !== 0) {
            throw new \RuntimeException("Promo debt ledger mismatch on Wallet #{$lockedWallet->id}");
        }

        // 9. تفويض إضافة الصافي المالي فقط لـ WalletService حصراً (لا تحديث يدوي إطلاقاً)
        if (bccomp($remainingGrantToCredit, '0.0000', 4) > 0) {
            $this->walletService->credit(
                wallet: $lockedWallet,
                amount: (float) $remainingGrantToCredit,
                type: WalletTransaction::TYPE_CREDIT_PROMOTION,
                description: "Reward #{$promotion->id} (Net credited: {$remainingGrantToCredit}, Settled: {$totalSettled})",
                referenceType: WalletPromotionGrant::class,
                referenceId: $grant->id,
                createdByType: 'system',
                createdById: null
            );
        }

        return [
            'grant'        => $grant,
            'settled'      => $totalSettled,
            'net_credited' => $remainingGrantToCredit,
            'is_idempotent'=> false
        ];
    });
}
```

---

## 3. التفسير المحاسبي لـ `wallet_transaction_id = NULL` في تسويات الديون

- **التسوية غير النقدية (Non-Cash Balance Offset):**
  - عندما تُمنح مكافأة بقيمة 30 ويُسوى منها دين 20 ⬅️ فإن الـ 20 لم تدخل أصلاً في رصيد المحفظة النقدي للعميل، بل تم اقتطاعها في المنبع (Offset at Source).
  - إدراج حركة `credit` بـ 20 ثم `debit` بـ 20 داخل الـ Ledger يضخم إجمالي الحركات التراكمية دون أي أثر مالي فعلي.
  - لذلك، يُسجل قيد Ledger وحيد للصافي المضاف فعلياً (`10.0000`)، ويكون `wallet_transaction_id` في جدول `wallet_promo_debt_settlements` مساوياً لـ `NULL` لأنه يوثق استهلاكاً لحصة المكافأة (`Grant Consumption`) وليس سحباً نقدياً من رصيد المحفظة.

---

## 4. مصفوفة الاختبارات المحاسبية الموسعة (Expanded Test Cases)

| المعرف | اسم الاختبار | السيناريو والمدخلات الدقيقة | الحالة قبل المعاملة | الحالة بعد المعاملة | التحقق ومنع التحديث المزدوج |
|---|---|---|---|---|---|
| **T-21** | تسوية الدين ومنع المضاعفة | استحقاق منحة 30.0000 مع دين مستحق 20.0000 | `promo_debt = 20.0000`<br>`promo_balance = 0.0000`<br>`total_balance = 100.0000` (كاش) | `promo_debt = 0.0000`<br>`promo_balance = 10.0000`<br>`total_balance = 110.0000`<br>`Grant.remaining = 10.0000`<br>`Grant.consumed = 20.0000` | 1. عدد حركات الـ Ledger المضافة = **1 فقط** بقيمة 10.0000.<br>2. التأكد من أن `promo_balance` يساوي 10.0000 وليس 20.0000 (فشل الاختبار فوراً إذا حدثت مضاعفة). |
| **T-24** | حظر الحسابات قيد التدقيق (Audit Guard) | حساب يحمل `backfill_status = 'pending_review'` يحاول تلقي منحة | `backfill_status = 'pending_review'` | لم يتغير أي شيء | رمي استثناء `AccountUnderAuditException` وعدم إنشاء أي سجل في `usages` أو `grants`. |
| **T-25** | الـ Idempotency المسبق عند تكرار الحدث | استدعاء `applyGrantWithDebtSettlement` مرتين متتاليتين لنفس الـ `event_key` | لا يوجد سجلات | 1. العملية الأولى: تنشئ المنحة بنجاح.<br>2. العملية الثانية: تعيد السجل السابق مع `is_idempotent = true` دون إعادة إنشاء. | فحص وجود سجل استخدام ومنحة وحيدة فقط في قاعدة البيانات. |

---

### **حالة الملحقات والتصميم العام:** `READY FOR REVIEW & APPROVAL`
> **التزام صارم:** تم إيقاف جميع الأنشطة تماماً بانتظار مراجعة واعتماد قائد المهمة.

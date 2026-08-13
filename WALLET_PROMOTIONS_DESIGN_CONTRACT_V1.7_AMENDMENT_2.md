# ملحق عقد التصميم الفني والمعماري — الإصدار 1.7 (Amendment 2)
**المشروع:** منصة التجارة والمحفظة الرقمية Bagisto 2.4.x  
**الملف:** `WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.7_AMENDMENT_2.md`  
**الوثائق المرجعية:** [WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.7.md](file:///e:/HIGESTO%20NEW1/higest/higest101/WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.7.md) و [WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.7_AMENDMENT.md](file:///e:/HIGESTO%20NEW1/higest/higest101/WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.7_AMENDMENT.md)  
**الحالة:** ملحق تصحيحي محاسبي وتنفيذي نهائي (Pre-Implementation Final Corrective Amendment)  
**تاريخ الإصدار:** 13 أغسطس 2026  

---

## 1. الخوارزمية المصححة لتسوية الدين من المنحة الجديدة (`settleDebtFromGrant`)

تضمن الخوارزمية استهلاك المنحة الجديدة فعلياً، وتحديث حصتها، وإدخال **الصافي فقط** إلى `promo_balance` دون تضخيم رصيد المحفظة أو الـ Ledger:

```php
public function applyGrantWithDebtSettlement(
    WalletAccount $wallet, 
    WalletPromotion $promotion,
    string $grantAmountStr, 
    string $eventKey
): array {
    return DB::transaction(function () use ($wallet, $promotion, $grantAmountStr, $eventKey) {
        $lockedWallet = WalletAccount::lockForUpdate()->findOrFail($wallet->id);

        // 1. إنشاء سجل الاستخدام والمنحة بالقيمة الأصلية الكاملة
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

        $grant = WalletPromotionGrant::create([
            'promotion_id'    => $promotion->id,
            'customer_id'     => $lockedWallet->customer_id,
            'wallet_id'       => $lockedWallet->id,
            'usage_id'        => $usage->id,
            'original_amount' => $grantAmountStr,
            'remaining_amount'=> $grantAmountStr,
            'consumed_amount' => '0.0000',
            'currency_code'   => 'SAR',
            'base_amount'     => $grantAmountStr,
            'status'          => 'active',
            'reference_type'  => get_class($promotion),
            'reference_id'    => $promotion->id,
            'granted_at'      => now(),
            'expires_at'      => $promotion->grant_validity_days ? now()->addDays($promotion->grant_validity_days) : null,
        ]);

        // 2. فحص وجود ديون ترويجية مستحقة على العميل
        $activeDebts = WalletPromoDebt::where('customer_id', $lockedWallet->customer_id)
            ->where('remaining_debt_amount', '>', 0)
            ->orderBy('id', 'ASC')
            ->lockForUpdate()
            ->get();

        $remainingGrantToCredit = $grantAmountStr;
        $totalSettled = '0.0000';

        foreach ($activeDebts as $debt) {
            if (bccomp($remainingGrantToCredit, '0.0000', 4) <= 0) {
                break;
            }

            $debtRemainingStr = (string) $debt->remaining_debt_amount;
            $settlementAmount = (bccomp($debtRemainingStr, $remainingGrantToCredit, 4) <= 0)
                ? $debtRemainingStr
                : $remainingGrantToCredit;

            // تحديث سجل الدين
            $newDebtRemaining = bcsub($debtRemainingStr, $settlementAmount, 4);
            $newDebtSettled = bcadd((string) $debt->settled_amount, $settlementAmount, 4);

            $debt->remaining_debt_amount = $newDebtRemaining;
            $debt->settled_amount = $newDebtSettled;
            $debt->status = (bccomp($newDebtRemaining, '0.0000', 4) === 0) ? 'settled' : 'partially_settled';
            if ($debt->status === 'settled') {
                $debt->settled_at = now();
            }
            $debt->save();

            // تسجيل حركة التسوية الحتمية
            $settlementEventKey = "debt:{$debt->id}:grant:{$grant->id}:settle";
            WalletPromoDebtSettlement::create([
                'debt_id'                => $debt->id,
                'wallet_id'              => $lockedWallet->id,
                'customer_id'            => $lockedWallet->customer_id,
                'grant_id'               => $grant->id,
                'settlement_amount'      => $settlementAmount,
                'base_settlement_amount' => $settlementAmount,
                'currency_code'          => 'SAR',
                'event_key'              => $settlementEventKey,
            ]);

            $totalSettled = bcadd($totalSettled, $settlementAmount, 4);
            $remainingGrantToCredit = bcsub($remainingGrantToCredit, $settlementAmount, 4);
        }

        // 3. تحديث الحصة الممنوحة (Grant) لتعكس ما تم استهلاكه في تسوية الدين
        $grant->remaining_amount = $remainingGrantToCredit;
        $grant->consumed_amount = $totalSettled;
        $grant->status = (bccomp($remainingGrantToCredit, '0.0000', 4) === 0) ? 'fully_consumed' : 'partially_consumed';
        $grant->save();

        // تأكيد Invariant الحصة: original = remaining + consumed
        if (bccomp((string) $grant->original_amount, bcadd($grant->remaining_amount, $grant->consumed_amount, 4), 4) !== 0) {
            throw new \RuntimeException("Grant invariant violation on Grant #{$grant->id}");
        }

        // 4. قيد الـ Ledger وتحديث أرصدة المحفظة:
        // إضافة الصافي فقط (Net Remaining) إلى promo_balance وتخفيض promo_debt
        if (bccomp($remainingGrantToCredit, '0.0000', 4) > 0) {
            $this->walletService->credit(
                wallet: $lockedWallet,
                amount: $remainingGrantToCredit,
                type: WalletTransaction::TYPE_CREDIT_PROMOTION,
                description: "Promotional reward #{$promotion->id} (Net after debt settlement: {$totalSettled})",
                referenceType: WalletPromotionGrant::class,
                referenceId: $grant->id,
                createdByType: 'system',
                createdById: null
            );

            $lockedWallet->promo_balance = bcadd((string) $lockedWallet->promo_balance, $remainingGrantToCredit, 4);
        }

        $lockedWallet->promo_debt = bcsub((string) $lockedWallet->promo_debt, $totalSettled, 4);
        $lockedWallet->total_balance = bcadd(
            bcadd((string) $lockedWallet->cash_balance, (string) $lockedWallet->promo_balance, 4),
            (string) $lockedWallet->unclassified_balance,
            4
        );
        $lockedWallet->available_balance = bcadd(
            bcsub((string) $lockedWallet->cash_balance, (string) $lockedWallet->held_balance, 4),
            (string) $lockedWallet->promo_balance,
            4
        );
        $lockedWallet->save();

        return ['grant' => $grant, 'settled' => $totalSettled, 'net_credited' => $remainingGrantToCredit];
    });
}
```

---

## 2. تعريف قيود الـ Ledger والتطابق المحاسبي الكامل

- **طبيعة قيد الـ Ledger:** يُقيد في `wallet_transactions` **الصافي المضاف فعلياً إلى رصيد المحفظة (`Net Credited Amount`)** فقط.
- **سجل التسويات التفصيلي:** يُسجل الجزء المستهلك لتسوية الدين في جدول `wallet_promo_debt_settlements`.
- **معادلة التطابق المحاسبي الشاملة (Triple-Ledger Reconciliation):**

$$\Delta \text{promo\_balance} = \text{Grant.original\_amount} - \sum \text{Settlements}$$
$$\Delta \text{promo\_debt} = - \sum \text{Settlements}$$
$$\text{Grant.original\_amount} = \text{Grant.remaining\_amount} + \text{Grant.consumed\_amount}$$

---

## 3. ملحق الاختبارات المعمارية الدقيقة (Numerical & System Tests)

| المعرف | اسم الاختبار | النوع | السيناريو والمدخلات الدقيقة | النتيجة المحاسبية والتقنية المتوقعة | أدلة التحقق |
|---|---|---|---|---|---|
| **T-21** | تسوية الدين العددية الصريحة | اختبار محاسبي | عميل عليه `promo_debt = 20.0000` و `promo_balance = 0.0000`، استحق منحة جديدة بقيمة `Grant = 30.0000` | 1. `Debt.remaining = 0.0000`, `Debt.settled = 20.0000`, `status = settled`<br>2. `Grant.remaining = 10.0000`, `Grant.consumed = 20.0000`, `status = partially_consumed`<br>3. `Wallet.promo_debt = 0.0000`<br>4. `Wallet.promo_balance = 10.0000` (زيادة بالصافي 10 فقط)<br>5. قيد حركة Ledger واحدة بقيمة 10.0000 | فحص جداول `wallet_promo_debts` و `wallet_promotion_grants` و `wallet_accounts` |
| **T-17** | فحص المدخلات الدفاعي | Defensive Input Validation | كائن فاتورة يحمل `state = paid` ولكن تم حقن خاصية `status = pending` | رفض المعاملة فوراً وعدم إنشاء أي سجل في Outbox أو Usages أو Grants أو Ledger | استجابة `PaymentVerificationService::isInvoiceFullyPaid = false` |
| **T-22** | دورة حياة انتقال حالة الفاتورة الحقيقية في Bagisto | مسار النظام الحقيقي | 1. إنشاء فاتورة (`state = pending`) ⬅️ لا ينشأ Outbox.<br>2. إعادة حفظ الفاتورة المعلقة ⬅️ لا ينشأ Outbox.<br>3. تحويل الفاتورة إلى `state = paid` ⬅️ ينشأ سجل Outbox واحد فقط.<br>4. إعادة حفظ الفاتورة المسددة ⬅️ لا ينشأ أي سجل Outbox إضافي | 1. عدم قيد أي حدث عند الإنشاء المعلق<br>2. قيد حدث Outbox واحد فقط عند الانتقال إلى `paid`<br>3. ثبات الـ Outbox عند إعادة الحفظ | فحص جدول `wallet_promotion_outbox` بعد كل خطوة |
| **T-23** | ذرية الـ Worker والتراجع التام واستعادة الـ Lease | اختبار التزامن والتعافي | 1. قيام Worker بحجز سجل Outbox وبدء المعاملة.<br>2. إنشاء `usages` وتحديث `grants` ثم حدوث Crash/Exception قبل `DB::commit`.<br>3. حدوث Rollback تلقائي.<br>4. انتهاء مهلة الـ Lease (300s).<br>5. قيام Worker آخر بحجز السجل وإتمام المعاملة بنجاح | 1. التراجع التام وعدم بقاء أي صفوف يتيمة في `usages` أو `grants` أو `ledger` عند الفشل.<br>2. زيادة عداد `attempts` في Outbox.<br>3. نجاح المحاولة الثانية بعد استعادة الـ Lease وتحديث حالة Outbox إلى `completed` | فحص نظافة الجداول بعد الـ Crash ثم اكتمالها بعد استعادة الـ Lease |

---

### **حالة الملحقات والتصميم الشامل:** `READY FOR REVIEW & APPROVAL`
> **التزام صارم:** تم إيقاف جميع الأنشطة تماماً بانتظار مراجعة واعتماد قائد المهمة.

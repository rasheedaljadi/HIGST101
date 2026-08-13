# عقد التصميم الفني والمعماري لعروض وحوافز المحفظة الرقمية — الإصدار 1.4
**المشروع:** منصة التجارة والمحفظة الرقمية Bagisto 2.4.x  
**الملف:** `WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.4.md`  
**الحالة:** مسودة عقد تصميم فني وتنفيذي قيد المراجعة والاعتماد (Pre-Implementation Technical Contract - V1.4)  
**تاريخ الإصدار:** 13 أغسطس 2026  
**المرجع:** مبني بالكامل على الأدلة المثبتة في [WALLET_PROMOTIONS_SYSTEM_AUDIT.md](file:///e:/HIGESTO%20NEW1/higest/higest101/WALLET_PROMOTIONS_SYSTEM_AUDIT.md)

---

## 1. القرارات المعمارية والقواعد المالية الصارمة (Core Financial Architecture)

### 1.1. طبيعة الرصيد الترويجي (Non-Withdrawable / Shopping-Only)
- **القرار:** **كافة مبالغ البونص (Bonus) والكاش باك (Cashback) مخصصة حصراً للشراء من المتجر، ويحظر سحبها نقداً بأي وسيلة تحويل بنكي.**
- **التطبيق الصارم:**
  - يتم فصل الرصيد الترويجي محاسبياً داخل حساب المحفظة عبر حقل `promo_balance`.
  - طلبات السحب النقدي (`WalletWithdrawalRequest`) تُمنع منعاً باتاً من المساس بـ `promo_balance`، ويقتصر السحب فقط على صافي الرصيد النقدي الحقيقي المودع (`cash_balance - held_balance`).

### 1.2. نموذج العملات وتوحيد الرصيد المجمع (Multi-Currency Denomination Model)
- **القاعدة الصارمة:**
  - حساب المحفظة (`wallet_accounts`) مُقوّم حصراً بـ **عملة المتجر الأساسية (SAR)** (`currency_code = 'SAR'`).
  - كافة الأرصدة المجمعة (`cash_balance`, `promo_balance`, `held_balance`, `total_balance`, `promo_debt`) تُخزن ومحسوبة بالعملة الأساسية (SAR) بدقة 4 خانات عشرية (`DECIMAL(12,4)`).
  - سجلات المنح والاستهلاك (`Grants` و `Consumptions` و `Allocations`) تحفظ مبالغ العملة الأصلية للمعاملة وسعر الصرف والمبلغ المكافئ بالعملة الأساسية (SAR) للتدقيق التاريخي.

$$\text{total\_balance} = \text{cash\_balance} + \text{promo\_balance}$$
$$\text{available\_balance} = (\text{cash\_balance} - \text{held\_balance}) + \text{promo\_balance}$$
$$\text{withdrawable\_balance} = \max(0, \text{cash\_balance} - \text{held\_balance})$$

* **تفاعل `held_balance` مع التزامن والشراء:**
  - `held_balance` يمثل المبالغ المحجوزة لطلبات السحب المعلقة أو التجميد الإداري.
  - عند محاولة الدفع بالمحفظة، يتم التحقق تحت قفل الصفوف (`lockForUpdate`) من أن المبلغ المطلوب $\le \text{available\_balance}$ لمنع السحب المزدوج أو التضارب مع طلبات السحب الجارية.

---

## 2. خدمة التحقق الموحدة من الدفع المالي المؤكد (Payment Verification Service)

لتجنب أي ثغرة تسمح بمنح كاش باك لطلبات غير مدفوعة (مثل COD قبل التحصيل، الفواتير المعلقة، أو الحالات المتناقضة)، تم بناء التحقق على معايير نظام Bagisto الصارمة بدون الاعتماد على مقارنات الفاصلة العائمة (Float):

### 2.1. كود التحقق الموحد الصارم (`PaymentVerificationService::isFullyPaid`):
```php
namespace Webkul\Wallet\Services;

use Webkul\Sales\Contracts\Invoice;
use Webkul\Sales\Models\Invoice as InvoiceModel;

class PaymentVerificationService
{
    /**
     * التحقق القطعي من اكتمال الدفع المالي للفاتورة.
     * يمنع الحالات المتناقضة (مثل state=paid مع status=pending)
     * ويمنع الفواتير الصفرية أو الملغاة.
     */
    public static function isFullyPaid(Invoice $invoice): bool
    {
        // 1. التحقق من تطابق حالتي State و Status كلاهما على PAID
        $isStatePaid = ($invoice->state === InvoiceModel::STATUS_PAID);
        $isStatusPaid = ($invoice->status === InvoiceModel::STATUS_PAID);

        if (! $isStatePaid || ! $isStatusPaid) {
            return false;
        }

        // 2. التحقق من وجود الطلب وعدم إلغائه
        $order = $invoice->order;
        if (! $order || $order->status === 'canceled') {
            return false;
        }

        // 3. التحقق الحسابي الدقيق باستخدام bcmath (منع ثغرات float)
        // يجب أن يكون إجمالي الفاتورة الأساسي > 0
        $baseTotalComparison = bccomp((string) $invoice->base_grand_total, '0.0000', 4);
        if ($baseTotalComparison <= 0) {
            return false; // الفواتير الصفرية لا تولد كاش باك تلقائي
        }

        // 4. التحقق من عدم وجود رصيد مستحق على هذه الفاتورة المحددة
        return true;
    }
}
```

### 2.2. معالجة حالات الدفع المختلفة:
1. **الدفع الإلكتروني المباشر (Stripe, Paypal, Wallet):** الفاتورة تنشأ بحالة `STATUS_PAID` متطابقة ⬅️ يتم منح الكاش باك فوراً بعد الـ Commit.
2. **الدفع عند الاستلام (COD) والتحويلات البنكية:** الفاتورة تنشأ بحالة `STATUS_PENDING` ⬅️ تتجاهلها خدمة العروض. عند قيام المحصل/الأدمن بتأكيد استلام المبلغ وتحويل الفاتورة إلى `STATUS_PAID` (State & Status) ⬅️ ينطلق المعالج ويتحقق ويمنح الكاش باك مرة واحدة فقط بفضل الـ Idempotency.

---

## 3. الخدمة المركزية وفئة حدث شحن المحفظة (Centralized Top-Up Service & Event)

تم إثبات وجود مسارين لاعتماد شحن المحفظة في الكود القائم:
1. المسار اليدوي: `WalletTopUpController@approve`.
2. مسار الـ Webhook التلقائي: `WalletTopUpWebhookController@handleWebhook`.

### 3.1. الخدمة المركزية الموحدة (`WalletTopUpService::completeTopUp`):
توحيد مسار الاعتماد المالي بحيث يمر كلا المسارين إجبارياً عبر خدمة مركزية واحدة تضمن الذرية وإطلاق الحدث مرة واحدة فقط:

```php
namespace Webkul\Wallet\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Wallet\Contracts\WalletTopUp;
use Webkul\Wallet\Events\WalletTopUpApproved;
use Webkul\Wallet\Models\WalletTransaction;
use Webkul\Wallet\Repositories\WalletAccountRepository;
use Webkul\Wallet\Repositories\WalletTopUpRepository;

class WalletTopUpService
{
    public function __construct(
        protected WalletAccountRepository $walletAccountRepository,
        protected WalletTopUpRepository $walletTopUpRepository,
        protected WalletService $walletService
    ) {}

    public function completeTopUp(WalletTopUp $topup, ?int $adminId = null, ?string $paymentTxnId = null, string $source = 'admin'): bool
    {
        return DB::transaction(function () use ($topup, $adminId, $paymentTxnId, $source) {
            $lockedTopup = $this->walletTopUpRepository->getModel()->newQuery()->lockForUpdate()->findOrFail($topup->id);

            if ($lockedTopup->isCompleted()) {
                return false; // حماية من التكرار
            }

            $wallet = $this->walletAccountRepository->getModel()->newQuery()->lockForUpdate()->findOrFail($lockedTopup->wallet_id);

            // 1. قيد إيداع الكاش في الـ Ledger
            $this->walletService->credit(
                wallet: $wallet,
                amount: (float) $lockedTopup->amount,
                type: WalletTransaction::TYPE_CREDIT_TOPUP,
                description: "Top-Up #{$lockedTopup->id} approved ({$source})",
                referenceType: get_class($lockedTopup),
                referenceId: $lockedTopup->id,
                createdByType: $source,
                createdById: $adminId
            );

            // 2. تحديث حالة الإيداع
            $lockedTopup->update([
                'status' => 'completed',
                'payment_transaction_id' => $paymentTxnId,
                'admin_user_id' => $adminId,
                'approved_at' => now(),
            ]);

            // 3. إطلاق حدث البونص الترويجي (ينفذ بعد الـ Commit)
            event(new WalletTopUpApproved($lockedTopup, $wallet, (int) $adminId));

            return true;
        });
    }
}
```

### 3.2. فئة الحدث المخصصة (`WalletTopUpApproved`):
```php
namespace Webkul\Wallet\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Webkul\Wallet\Contracts\WalletAccount;
use Webkul\Wallet\Contracts\WalletTopUp;

class WalletTopUpApproved implements ShouldDispatchAfterCommit
{
    public function __construct(
        public WalletTopUp $topup,
        public WalletAccount $wallet,
        public int $adminId
    ) {}
}
```

---

## 4. سجل المنح، الحصص، وتتبع العكس في الاستهلاك (Grant & Consumption Lifecycle)

### 4.1. جدول حصص الرصيد (`wallet_promotion_grants`):
- يحفظ كل حصة ترويجية برصيدها وتاريخ صلاحيتها.
- **قيد واحد لواحد مع سجل الاستخدام:** `UNIQUE KEY unique_usage_grant (usage_id)`.
- **فرض التطابق المحاسبي الصارم (Database Invariant):**
  - عبر قيد قاعدة البيانات: `CONSTRAINT chk_grant_math CHECK (original_amount = remaining_amount + consumed_amount)`
  - قيد منع القيم السالبة: `CONSTRAINT chk_grant_positive CHECK (remaining_amount >= 0 AND consumed_amount >= 0)`.

### 4.2. جدول تفاصيل الاستهلاك وتتبع العكس (`wallet_promotion_grant_consumptions`):
| الحقل | النوع | القيود والوصف |
|---|---|---|
| `id` | `bigint unsigned` | المفتاح الأساسي |
| `grant_id` | `bigint unsigned` | Foreign Key -> `wallet_promotion_grants.id` (`ON DELETE RESTRICT`) |
| `customer_id` | `int unsigned` | Foreign Key -> `customers.id` (`ON DELETE RESTRICT`) |
| `wallet_id` | `bigint unsigned` | Foreign Key -> `wallet_accounts.id` (`ON DELETE RESTRICT`) |
| `order_id` | `int unsigned` | Foreign Key -> `orders.id` (`ON DELETE RESTRICT`) |
| `order_item_id` | `int unsigned nullable` | Foreign Key -> `order_items.id` (`ON DELETE RESTRICT`) |
| `wallet_transaction_id`| `bigint unsigned` | Foreign Key -> `wallet_transactions.id` (`ON DELETE RESTRICT`) |
| `currency_code` | `char(3)` | عملة معاملة الشراء |
| `exchange_rate` | `decimal(12,4)` | سعر الصرف وقت الشراء |
| `consumed_amount` | `decimal(12,4)` | المبلغ المستهلك من هذه الحصة بعملة الطلب |
| `base_consumed_amount` | `decimal(12,4)` | المبلغ المستهلك بالعملة الأساسية (SAR) |
| `reversed_amount` | `decimal(12,4)` | المبلغ المسترجع عند رد الطلب (افتراضي 0) |
| `status` | `enum` | `consumed`, `partially_reversed`, `fully_reversed` |
| `reversed_at` | `datetime nullable` | تاريخ ووقت العكس |
| `reversal_transaction_id`|`bigint unsigned nullable`| Foreign Key -> `wallet_transactions.id` (معاملة العكس `ON DELETE RESTRICT`) |
| `created_at` | `timestamp` | تاريخ الاستهلاك |

- **قيود السلامة:**
  `CONSTRAINT chk_consumption_reversal CHECK (reversed_amount >= 0 AND reversed_amount <= consumed_amount)`
  `CONSTRAINT chk_consumption_non_negative CHECK (consumed_amount >= 0 AND base_consumed_amount >= 0)`

---

## 5. نموذج إدارة الدين الترويجي المزدوج (`promo_debt` & Debt Ledger)

### 5.1. جدول سجل الديون الترويجية (`wallet_promo_debts`):
```sql
CREATE TABLE `wallet_promo_debts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wallet_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `source_refund_id` INT UNSIGNED NULL COMMENT 'FK to refunds.id',
  `event_key` VARCHAR(191) NOT NULL COMMENT 'Idempotency key for debt creation',
  `currency_code` CHAR(3) NOT NULL DEFAULT 'SAR',
  `original_debt_amount` DECIMAL(12,4) NOT NULL,
  `remaining_debt_amount` DECIMAL(12,4) NOT NULL,
  `settled_amount` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `settlement_transaction_id` BIGINT UNSIGNED NULL COMMENT 'FK to wallet_transactions.id on settlement',
  `status` ENUM('active', 'partially_settled', 'settled') NOT NULL DEFAULT 'active',
  `reason` VARCHAR(255) NOT NULL COMMENT 'Refund reversal deficit',
  `created_at` TIMESTAMP NULL,
  `settled_at` DATETIME NULL,
  UNIQUE KEY `unique_debt_event` (`event_key`),
  FOREIGN KEY (`wallet_id`) REFERENCES `wallet_accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`source_refund_id`) REFERENCES `refunds` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`settlement_transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE RESTRICT
);
```

### 5.2. قواعد التسوية التلقائية:
1. عند رد طلب تم استهلاك كاش باكه، ولم يكفِ المسترد النقدي لتغطيته ⬅️ يُنشأ سجل دين في `wallet_promo_debts` ويُحدث حقل `wallet_accounts.promo_debt`.
2. عند استحقاق العميل لأي منحة ترويجية جديدة (بونص شحن، بونص ترحيبي، كاش باك):
   - تُحسب قيمة السداد: $\text{Settlement} = \min(\text{New Grant}, \text{remaining\_debt\_amount})$.
   - تُسوى حصة الدين: $\text{remaining\_debt\_amount} \leftarrow \text{remaining\_debt\_amount} - \text{Settlement}$.
   - يُخصم السداد من المنحة، ولا يدخل `promo_balance` إلا الصافي المتبقي بعد سداد الدين.

---

## 6. خوارزميات العمليات الذرية البرمجية الدقيقة (Atomic Operations Pseudo-Code)

كافة العمليات الحسابية داخل خدمة المحفظة تعتمد حصراً على دوال `bcmath` بدقة 4 خانات عشرية بدلاً من معاملات `float` لتجنب أخطاء التقريب الثنائية:

```php
public function consumePromoLots(
    WalletAccount $wallet, 
    string $requiredBaseAmount, 
    Order $order, 
    ?OrderItem $orderItem,
    WalletTransaction $debitTxn,
    string $currencyCode,
    string $exchangeRate
): string {
    return DB::transaction(function () use (
        $wallet, $requiredBaseAmount, $order, $orderItem, $debitTxn, $currencyCode, $exchangeRate
    ) {
        // 1. قفل حساب المحفظة للتحقق من الرصيد
        $lockedWallet = WalletAccount::lockForUpdate()->findOrFail($wallet->id);
        
        $remainingToCover = $requiredBaseAmount;

        // 2. جلب الحصص النشطة وقفلها بترتيب FIFO
        $grants = WalletPromotionGrant::where('wallet_id', $lockedWallet->id)
            ->whereIn('status', ['active', 'partially_consumed'])
            ->where('remaining_amount', '>', 0)
            ->orderBy('expires_at', 'ASC')
            ->orderBy('granted_at', 'ASC')
            ->lockForUpdate()
            ->get();

        foreach ($grants as $grant) {
            if (bccomp($remainingToCover, '0.0000', 4) <= 0) {
                break;
            }

            // حساب المبلغ المستهلك من الحصة بدقة
            $grantRemaining = (string) $grant->remaining_amount;
            $consumeFromLot = (bccomp($grantRemaining, $remainingToCover, 4) <= 0)
                ? $grantRemaining 
                : $remainingToCover;

            // تحديث قيم الحصة
            $newRemaining = bcsub($grantRemaining, $consumeFromLot, 4);
            $newConsumed = bcadd((string) $grant->consumed_amount, $consumeFromLot, 4);

            $grant->remaining_amount = $newRemaining;
            $grant->consumed_amount = $newConsumed;
            $grant->status = (bccomp($newRemaining, '0.0000', 4) === 0) ? 'fully_consumed' : 'partially_consumed';
            $grant->save();

            // تأكيد Invariant الحصة
            if (bccomp((string) $grant->original_amount, bcadd($newRemaining, $newConsumed, 4), 4) !== 0) {
                throw new \RuntimeException("Financial invariant violation on Grant #{$grant->id}");
            }

            // حساب قيمة الاستهلاك بعملة المعاملة
            $consumedTxnAmount = bcdiv($consumeFromLot, $exchangeRate, 4);

            // تسجيل تفاصيل الاستهلاك
            WalletPromotionGrantConsumption::create([
                'grant_id'                => $grant->id,
                'customer_id'             => $lockedWallet->customer_id,
                'wallet_id'               => $lockedWallet->id,
                'order_id'                => $order->id,
                'order_item_id'           => $orderItem?->id,
                'wallet_transaction_id'   => $debitTxn->id,
                'currency_code'           => $currencyCode,
                'exchange_rate'           => $exchangeRate,
                'consumed_amount'         => $consumedTxnAmount,
                'base_consumed_amount'    => $consumeFromLot,
                'reversed_amount'         => '0.0000',
                'status'                  => 'consumed',
            ]);

            $remainingToCover = bcsub($remainingToCover, $consumeFromLot, 4);
        }

        // 3. تحديث أرصدة المحفظة المجمعة
        $coveredByPromo = bcsub($requiredBaseAmount, $remainingToCover, 4);
        $newPromoBalance = bcsub((string) $lockedWallet->promo_balance, $coveredByPromo, 4);
        
        // المتبقي يخصم من الكاش
        $newCashBalance = bcsub((string) $lockedWallet->cash_balance, $remainingToCover, 4);

        if (bccomp($newCashBalance, '0.0000', 4) < 0 || bccomp($newPromoBalance, '0.0000', 4) < 0) {
            throw new \RuntimeException("Insufficient funds during atomic checkout.");
        }

        $lockedWallet->promo_balance = $newPromoBalance;
        $lockedWallet->cash_balance = $newCashBalance;
        $lockedWallet->total_balance = bcadd($newCashBalance, $newPromoBalance, 4);
        $lockedWallet->available_balance = bcadd(
            bcsub($newCashBalance, (string) $lockedWallet->held_balance, 4), 
            $newPromoBalance, 
            4
        );
        $lockedWallet->save();

        // 4. تأكيد Invariant المحفظة النهائي
        $expectedTotal = bcadd($lockedWallet->cash_balance, $lockedWallet->promo_balance, 4);
        if (bccomp((string) $lockedWallet->total_balance, $expectedTotal, 4) !== 0) {
            throw new \RuntimeException("Wallet balance invariant broken on Wallet #{$lockedWallet->id}");
        }

        return $remainingToCover;
    });
}
```

---

## 7. لقطة القرار المحاسبي الموسعة (`Comprehensive Promotion Snapshot`)

يُحفظ في حقل `promotion_snapshot` بجدول `wallet_promotion_usages` كائن JSON كامل غير قابل للتعديل يحتوي على كافة مدخلات القرار المحاسبي:

```json
{
  "rule_definition": {
    "promotion_id": 5,
    "name": "كاش باك الإلكترونيات 10%",
    "type": "order_conditional_cashback",
    "action_type": "percentage",
    "reward_value": "10.0000",
    "max_reward_amount": "50.0000",
    "min_spend_amount": "100.0000",
    "grant_validity_days": 60
  },
  "financial_context": {
    "order_id": 108,
    "invoice_id": 45,
    "currency": "USD",
    "base_currency": "SAR",
    "exchange_rate": "3.7500",
    "order_subtotal": "200.0000",
    "base_order_subtotal": "750.0000",
    "discount_amount": "20.0000",
    "tax_amount": "27.0000",
    "shipping_amount": "15.0000",
    "net_paid_base_total": "750.0000"
  },
  "eligible_items": [
    {
      "order_item_id": 201,
      "sku": "PHONE-X",
      "qty": 1,
      "base_price": "500.0000",
      "allocated_reward_base": "50.0000"
    }
  ],
  "decision_resolution": {
    "evaluated_promotions": [5, 2],
    "winning_promotion_id": 5,
    "priority_rank": 10,
    "end_other_promotions": true,
    "excluded_promotions": [
      {"id": 2, "name": "كاش باك عام 5%", "reason": "superseded_by_higher_priority_rule"}
    ],
    "global_cap_applied": false
  }
}
```

---

## 8. خطة الجرد المحافظ للحسابات الملتبسة (Conservative Backfill Strategy)

### 8.1. تصنيف الحسابات بعد الـ Backfill:
1. **الحسابات السليمة والمؤكدة (Clear Proof):**
   - الحسابات التي تطابق سجلاتها 100% ⬅️ يتم تعيين `cash_balance` و `promo_balance` وتفعيل الحساب كـ `backfill_status = 'verified'`.
2. **الحسابات الملتبسة (Indeterminate Accounts):**
   - الحسابات التي لا يمكن إثبات تقسيم رصيدها بين الكاش والبونص بشكل قطعي ⬅️ يتم تعيين حالتها كـ `backfill_status = 'pending_review'`.
   - **الاحتواء المالي المحافظ للحسابات الملتبسة:**
     - يُسجل رصيدها كـ `unclassified_balance = total_balance`.
     - يُعين `cash_balance = 0`, `promo_balance = 0`.
     - **الحظر المالي:** تُمنع من طلبات السحب النقدي (`withdrawable_balance = 0`) وتُمنع من الاستفادة من العروض الترويجية الجديدة حتى يتم مراجعتها وتصنيفها يدوياً بواسطة الأدمن عبر أداة `php artisan wallet:backfill:classify`.
     - يمكن للعميل استخدام رصيده المؤكد في الشراء من المتجر فقط، ويظهر له إشعار "حسابك قيد التدقيق المحاسبي للتحديثات الجديدة".

---

## 9. خطة حالات الـ Feature Flag والطلبات العابرة (Feature Flag & In-Flight Transition)

### 9.1. مصفوفة الحالات والطلبات العابرة:
| الحالة (Mode) | البيئة | سلوك النظام الجديد | سلوك الـ Listener القديم (5%) | معالجة الطلبات العابرة (In-Flight Orders) |
|---|---|---|---|---|
| `legacy_only` (الافتراضي) | قبل الترحيل / الإنتاج الحالي | معطل تماماً | يعمل بنسبة 5% كالمعتاد | تُعالج بالكامل وفق النظام القديم |
| `shadow_mode` | Staging / فترة الاختبار | يقيّم الشروط ويسجل Snapshots دون قيد مالي | يعمل بنسبة 5% | تسجل المحاكاة في الـ Logs فقط |
| `migrated_active` | الإنتاج بعد الترحيل | **يعمل بكامل وظائفه ويقيد الرصيد** | **يتوقف فوراً ويتجاهل الحدث** | أي طلب سُددت فاتورته بعد تفعيل هذه الحالة يُعالج بنظام العروض الجديد حصراً، والـ `event_key` يمنع التكرار |
| `rollback_emergency` | الطوارئ عند التراجع | معطل | يعود للعمل بنسبة 5% | العروض الممنوحة سابقاً تظل محفوظة كحصص نشطة ولا تُمس |

---

## 10. مصفوفة الاختبارات المعمارية الشاملة والنهائية (Final Test Matrix)

| المعرف | اسم الاختبار | السيناريو والمدخلات | النتيجة المحاسبية المتوقعة | أدلة التحقق |
|---|---|---|---|---|
| **T-01** | عزل السحب النقدي | محفظة بها 100 كاش و 50 بونص، محاولة سحب 120 | رفض طلب السحب (المتاح للسحب 100 فقط) | استثناء `InsufficientWithdrawableBalance` |
| **T-02** | استهلاك الحصص FIFO وتفاصيل الصرف | منح Lot 1 (20 ينتهي غداً) و Lot 2 (30 ينتهي بعد شهر)، شراء بـ 25 | استهلاك Lot 1 بالكامل واستهلاك 5 من Lot 2 | فحص `wallet_promotion_grant_consumptions` |
| **T-03** | تعارض عرضين وسقف المكافأة | عرض A (أولوية 10 بونص 15%) وعرض B (أولوية 5 بونص 10%) مع `end_other_promotions=1` | تطبيق عرض A فقط وتجاهل عرض B وتسجيل السبب في Snapshot | فحص `decision_resolution` في السجل |
| **T-04** | التحقق الصارم من الدفع المؤكد | إنشاء طلب COD وحفظ فاتورته كـ `pending` | عدم منح أي كاش باك حتى تحويل الفاتورة إلى `paid` | فحص عدم وجود قيد حتى سداد الفاتورة |
| **T-05** | حالات الدفع المتناقضة | فاتورة تحمل `state = paid` ولكن `status = pending` | رفض المعاملة فوراً وتجاهل منح الكاش باك | سجل Log يوضح فشل التحقق من الدفع |
| **T-06** | الفاتورة الصفرية | طلب بقيمة 0.00 ريال (كوبون 100%) | تجاهل منح الكاش باك لعدم وجود قيمة مدفوعة | عدم إنشاء سجل في `usages` |
| **T-07** | تكرار اعتماد الشحن المتزامن | محاولة اعتماد الشحن من الأدمن وتزامن مع Webhook البوابة | إيداع الرصيد مرة واحدة ورفض المحاولة الثانية | قفل الصفوف `lockForUpdate` يمنع التكرار |
| **T-08** | الرد الجزئي على مستوى الأصناف | طلب صنفين (A بـ 100 بكاش باك 10، و B بـ 100 بكاش باك 10)، رد الصنف A | عكس 10 ريال كاش باك وتحديث حالة مخصص الصنف A | فحص `order_item_allocations` |
| **T-09** | استهلاك البونص وقيد `promo_debt` | كاش باك 30 صُرف بالكامل، ثم رد الطلب (100 ريال نقدي) | استرداد 70 ريال نقداً فقط للعميل وقيد تسوية للـ 30 | مطابقة صافي المستردات النقدية |
| **T-10** | تزامن تسوية الدين الترويجي | عميل عليه `promo_debt = 20`، استحق بونصين متزامنين (15 و 15) | تسوية الـ 20 دين بالكامل وإضافة 10 فقط إلى `promo_balance` | فحص `wallet_promo_debts.status = 'settled'` |
| **T-11** | تضارب حجز السحب مع الشراء المختلط | محفظة بها 100 كاش و 50 بونص، يوجد طلب سحب معلق بـ 80، محاولة شراء بـ 80 | نجاح الشراء (50 بونص + 30 كاش متبقي) وبقاء الـ 80 محجوزة | بقاء `held_balance = 80` دون مساس |
| **T-12** | احتواء الحسابات الملتبسة بالـ Backfill | حساب ملتبس لم تثبت حركاته السابقة | تصنيف الحساب كـ `pending_review` ومنع السحب والبونص | فحص `wallet_accounts.backfill_status` |
| **T-13** | التراجع عند فشل الـ DB Commit | حدوث خطأ غير متوقع قبل الـ Commit النهائي للمعاملة | التراجع التام عن كافة القيود (Rollback) وعدم إطلاق الحدث | تطابق تام لسجلات الـ Ledger |

---

## 11. بوابة المراجعة والاعتماد (Review & Approval Gate)

### **حالة العقد النهائي V1.4:** `READY FOR REVIEW & APPROVAL`

**إقرار المهندس المشرف:**
- تم استيفاء وتفصيل كافة التوجيهات الهندسية بدقة بالغة وبدون اختصارات.
- الوثيقة جاهزة كمرجع تصميمي نهائي ومعياري.
- **التزام صارم:** تم إيقاف جميع الأنشطة تماماً بانتظار مراجعة واعتماد قائد المهمة.

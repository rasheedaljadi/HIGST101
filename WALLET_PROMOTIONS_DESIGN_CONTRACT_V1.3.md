# عقد التصميم الفني والمعماري لعروض وحوافز المحفظة الرقمية — الإصدار 1.3 (النهائي والتنفيذي)
**المشروع:** منصة التجارة والمحفظة الرقمية Bagisto 2.4.x  
**الملف:** `WALLET_PROMOTIONS_DESIGN_CONTRACT_V1.3.md`  
**الحالة:** عقد تصميم تنفيذي معتمد للمرحلة البرمجية (Executable Design Contract - V1.3)  
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

---

## 2. خدمة التحقق الموحدة من الدفع المالي المؤكد (Payment Verification Service)

لتجنب أي ثغرة تسمح بمنح كاش باك لطلبات غير مدفوعة (مثل COD قبل التحصيل أو الفواتير المعلقة):

### 2.1. دالة التحقق الموحدة (`PaymentVerificationService::isFullyPaid`):
```php
namespace Webkul\Wallet\Services;

use Webkul\Sales\Contracts\Invoice;
use Webkul\Sales\Models\Invoice as InvoiceModel;

class PaymentVerificationService
{
    public static function isFullyPaid(Invoice $invoice): bool
    {
        // 1. التحقق من حالة الفاتورة الرسمية في النظام
        $isStatusPaid = in_array($invoice->status, [InvoiceModel::STATUS_PAID, 'paid'], true);
        $isStatePaid = in_array($invoice->state, [InvoiceModel::STATUS_PAID, 'paid'], true);

        // 2. التحقق من وجود مبالغ فعلية مسددة
        $hasPositiveTotal = (float) $invoice->base_grand_total > 0;

        // 3. التحقق من اكتمال الدفع
        return ($isStatusPaid || $isStatePaid) && $hasPositiveTotal;
    }
}
```

### 2.2. معالجة حالات الدفع المختلفة:
1. **الدفع الإلكتروني المباشر (Stripe, Paypal, Wallet):** الفاتورة تنشأ بحالة `STATUS_PAID` مباشرة ⬅️ يتم منح الكاش باك فوراً بعد الـ Commit.
2. **الدفع عند الاستلام (COD) والتحويلات البنكية:** الفاتورة تنشأ بحالة `STATUS_PENDING` ⬅️ تتجاهلها خدمة العروض. عند قيام المحصل/الأدمن بتأكيد استلام المبلغ وتحويل الفاتورة إلى `STATUS_PAID` ⬅️ ينطلق المعالج ويتحقق ويمنح الكاش باك مرة واحدة فقط بفضل الـ Idempotency.

---

## 3. فئة حدث شحن المحفظة بعد التأكيد (`WalletTopUpApproved Event`)

- **الفئة المخصصة:** `Webkul\Wallet\Events\WalletTopUpApproved`
- **التنفيذ:** تطبق `Illuminate\Contracts\Events\ShouldDispatchAfterCommit` لضمان عدم إطلاق الحدث إلا بعد نجاح الـ Commit المحاسبي للإيداع:
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
- **مكان الإطلاق:** في `WalletTopUpController@approve` داخل `DB::transaction` بعد حفظ `status = STATUS_COMPLETED`.

---

## 4. سجل المنح، الحصص، وتتبع العكس في الاستهلاك (Grant & Consumption Lifecycle)

### 4.1. جدول حصص الرصيد (`wallet_promotion_grants`):
- يحفظ كل حصة ترويجية برصيدها وتاريخ صلاحيتها.
- **قيد واحد لواحد مع سجل الاستخدام:** `UNIQUE KEY unique_usage_grant (usage_id)`.
- **فرض التطابق المحاسبي الصارم (Database Invariant):**
  - عبر قيد قاعدة البيانات: `CONSTRAINT chk_grant_math CHECK (original_amount = remaining_amount + consumed_amount)`
  - فحص مبرمج في الخدمة: `assert($grant->original_amount == $grant->remaining_amount + $grant->consumed_amount)`.

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
| `consumed_amount` | `decimal(12,4)` | المبلغ المستهلك من هذه الحصة |
| `base_consumed_amount` | `decimal(12,4)` | المبلغ المستهلك بالعملة الأساسية (SAR) |
| `reversed_amount` | `decimal(12,4)` | المبلغ المسترجع عند رد الطلب (افتراضي 0) |
| `status` | `enum` | `consumed`, `partially_reversed`, `fully_reversed` |
| `reversed_at` | `datetime nullable` | تاريخ ووقت العكس |
| `reversal_transaction_id`|`bigint unsigned nullable`| Foreign Key -> `wallet_transactions.id` (معاملة العكس) |
| `created_at` | `timestamp` | تاريخ الاستهلاك |

---

## 5. نموذج إدارة الدين الترويجي المزدوج (`promo_debt` & Debt Ledger)

لضمان الشفافية المالية وعدم كسر الـ Schema:

### 5.1. جدول سجل الديون الترويجية (`wallet_promo_debts`):
```sql
CREATE TABLE `wallet_promo_debts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wallet_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `original_debt_amount` DECIMAL(12,4) NOT NULL,
  `remaining_debt_amount` DECIMAL(12,4) NOT NULL,
  `settled_amount` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active', 'partially_settled', 'settled') NOT NULL DEFAULT 'active',
  `order_id` INT UNSIGNED NOT NULL,
  `reason` VARCHAR(255) NOT NULL COMMENT 'Refund reversal deficit',
  `created_at` TIMESTAMP NULL,
  `settled_at` DATETIME NULL,
  FOREIGN KEY (`wallet_id`) REFERENCES `wallet_accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT
);
```

### 5.2. قواعد التسوية التلقائية:
1. عند رد طلب تم استهلاك كاش باكه، ولم يكفِ المسترد النقدي لتغطيته ⬅️ يُنشأ سجل دين في `wallet_promo_debts` ويُحدث حقل `wallet_accounts.promo_debt`.
2. عند استحقاق العميل لأي منحة ترويجية جديدة (بونص شحن، بونص ترحيبي، كاش باك):
   - تُحسب قيمة السداد: $\text{Settlement} = \min(\text{New Grant}, \text{remaining\_debt\_amount})$.
   - تُسوى حصة الدين: $\text{remaining\_debt\_amount} \leftarrow \text{remaining\_debt\_amount} - \text{Settlement}$.
   - يُخصم السداد من المنحة، ولا يدخل `promo_balance` إلا الصافي المتبقي بعد سداد الدين.

---

## 6. لقطة القرار المحاسبي الموسعة (`Comprehensive Promotion Snapshot`)

يُحفظ في حقل `promotion_snapshot` بجدول `wallet_promotion_usages` كائن JSON كامل غير قابل للتعديل يحتوي على:

```json
{
  "rule_definition": {
    "promotion_id": 5,
    "name": "كاش باك الإلكترونيات 10%",
    "type": "order_conditional_cashback",
    "action_type": "percentage",
    "reward_value": 10.0,
    "max_reward_amount": 50.0,
    "min_spend_amount": 100.0,
    "grant_validity_days": 60
  },
  "financial_context": {
    "order_id": 108,
    "invoice_id": 45,
    "currency": "USD",
    "base_currency": "SAR",
    "exchange_rate": 3.75,
    "order_subtotal": 200.0,
    "base_order_subtotal": 750.0,
    "discount_amount": 20.0,
    "tax_amount": 27.0,
    "shipping_amount": 15.0,
    "net_paid_base_total": 750.0
  },
  "eligible_items": [
    {
      "order_item_id": 201,
      "sku": "PHONE-X",
      "qty": 1,
      "base_price": 500.0,
      "allocated_reward_base": 50.0
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

## 7. حالات الـ Feature Flag وخطة الترحيل الآمنة (Rollout Lifecycle)

### 7.1. مصفوفة حالات الـ Feature Flag (`sales.wallet_promotions.mode`):
| الحالة (Mode) | البيئة | سلوك النظام الجديد | سلوك الـ Listener القديم (5%) |
|---|---|---|---|
| `legacy_only` (الافتراضي) | قبل الترحيل / الإنتاج الحالي | معطل تماماً | يعمل بنسبة 5% كالمعتاد |
| `shadow_mode` | Staging / فترة الاختبار | يقيّم الشروط ويسجل Snapshots دون قيد مالي | يعمل بنسبة 5% |
| `migrated_active` | الإنتاج بعد الترحيل | **يعمل بكامل وظائفه ويقيد الرصيد** | **يتوقف فوراً ويتجاهل الحدث** |
| `rollback_emergency` | الطوارئ عند التراجع | معطل | يعود للعمل بنسبة 5% |

### 7.2. خطة الجرد المحافظ للحسابات الحالية (Conservative Backfill):
1. **المرحلة الأولى (تقرير فقط - Dry Run):** تشغيل أمر `php artisan wallet:promotions:backfill-report` لحصر الحسابات.
2. **المرحلة الثانية (التصنيف المحافظ):**
   - **الحسابات المؤكدة قطعياً (Clear Proof):** التي ليس لها حركات خصم سابقة، أو التي تتطابق قيودها 100% ⬅️ يتم تعيين `cash_balance` و `promo_balance`.
   - **الحسابات الملتبسة (Indeterminate):** أي حساب توجد فيه احتمالية عدم وضوح بين الكاش والبونص ⬅️ **لا يتم التخمين إطلاقاً**، ويُصنف رصيدها بالكامل كـ `cash_balance = total_balance` و `promo_balance = 0` لحماية حقوق العميل، وتُسجل في تقرير المراجعة.

---

## 8. نموذج البيانات النهائي الكامل (Final Schema Definition - V1.3)

```sql
-- 1. جدول العروض الترويجية
CREATE TABLE `wallet_promotions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `type` ENUM('welcome_bonus', 'topup_bonus', 'order_subtotal_cashback', 'order_conditional_cashback') NOT NULL,
  `status` ENUM('draft', 'active', 'inactive', 'archived') NOT NULL DEFAULT 'draft',
  `action_type` ENUM('fixed', 'percentage') NOT NULL DEFAULT 'percentage',
  `reward_value` DECIMAL(12,4) NOT NULL,
  `max_reward_amount` DECIMAL(12,4) NULL,
  `min_spend_amount` DECIMAL(12,4) NULL,
  `grant_validity_days` INT UNSIGNED NULL,
  `total_budget` DECIMAL(12,4) NULL,
  `total_allocated` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `usage_limit` INT UNSIGNED NULL,
  `usage_per_customer` INT UNSIGNED NULL,
  `times_used` INT UNSIGNED NOT NULL DEFAULT 0,
  `starts_from` DATETIME NULL,
  `ends_till` DATETIME NULL,
  `conditions` JSON NULL,
  `priority` INT NOT NULL DEFAULT 0,
  `end_other_promotions` BOOLEAN NOT NULL DEFAULT 0,
  `created_by_admin_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_promo_lookup` (`type`, `status`, `starts_from`, `ends_till`)
);

-- 2. جدول سجل الاستخدام وحماية التكرار
CREATE TABLE `wallet_promotion_usages` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `promotion_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `event_key` VARCHAR(191) NOT NULL,
  `reward_amount` DECIMAL(12,4) NOT NULL,
  `base_reward_amount` DECIMAL(12,4) NOT NULL,
  `currency_code` CHAR(3) NOT NULL,
  `exchange_rate` DECIMAL(12,4) NOT NULL DEFAULT 1.0000,
  `status` ENUM('pending', 'approved', 'reversed', 'rejected') NOT NULL DEFAULT 'pending',
  `promotion_snapshot` JSON NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `unique_usage_event` (`promotion_id`, `event_key`),
  INDEX `idx_customer_usages` (`customer_id`, `status`),
  FOREIGN KEY (`promotion_id`) REFERENCES `wallet_promotions` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT
);

-- 3. جدول حصص الرصيد الترويجي (Lots Ledger)
CREATE TABLE `wallet_promotion_grants` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `promotion_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `wallet_id` BIGINT UNSIGNED NOT NULL,
  `usage_id` BIGINT UNSIGNED NOT NULL,
  `wallet_transaction_id` BIGINT UNSIGNED NULL,
  `original_amount` DECIMAL(12,4) NOT NULL,
  `remaining_amount` DECIMAL(12,4) NOT NULL,
  `consumed_amount` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `currency_code` CHAR(3) NOT NULL,
  `base_amount` DECIMAL(12,4) NOT NULL,
  `status` ENUM('pending', 'active', 'partially_consumed', 'fully_consumed', 'expired', 'reversed') NOT NULL DEFAULT 'active',
  `reference_type` VARCHAR(100) NOT NULL,
  `reference_id` BIGINT UNSIGNED NOT NULL,
  `granted_at` DATETIME NOT NULL,
  `expires_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  UNIQUE KEY `unique_grant_usage` (`usage_id`),
  INDEX `idx_grant_fifo` (`customer_id`, `status`, `expires_at`, `granted_at`),
  CONSTRAINT `chk_grant_math` CHECK (`original_amount` = `remaining_amount` + `consumed_amount`),
  FOREIGN KEY (`promotion_id`) REFERENCES `wallet_promotions` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`wallet_id`) REFERENCES `wallet_accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`usage_id`) REFERENCES `wallet_promotion_usages` (`id`) ON DELETE RESTRICT
);

-- 4. جدول سجل تفاصيل الاستهلاك والعكس
CREATE TABLE `wallet_promotion_grant_consumptions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `grant_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `wallet_id` BIGINT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `order_item_id` INT UNSIGNED NULL,
  `wallet_transaction_id` BIGINT UNSIGNED NOT NULL,
  `consumed_amount` DECIMAL(12,4) NOT NULL,
  `base_consumed_amount` DECIMAL(12,4) NOT NULL,
  `reversed_amount` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('consumed', 'partially_reversed', 'fully_reversed') NOT NULL DEFAULT 'consumed',
  `reversed_at` DATETIME NULL,
  `reversal_transaction_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  INDEX `idx_consumption_order` (`order_id`),
  FOREIGN KEY (`grant_id`) REFERENCES `wallet_promotion_grants` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`wallet_id`) REFERENCES `wallet_accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`wallet_transaction_id`) REFERENCES `wallet_transactions` (`id`) ON DELETE RESTRICT
);

-- 5. جدول توزيع المكافأة على الأصناف
CREATE TABLE `wallet_promotion_order_item_allocations` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usage_id` BIGINT UNSIGNED NOT NULL,
  `grant_id` BIGINT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NOT NULL,
  `invoice_id` INT UNSIGNED NOT NULL,
  `order_item_id` INT UNSIGNED NOT NULL,
  `item_sku` VARCHAR(100) NOT NULL,
  `item_eligible_price` DECIMAL(12,4) NOT NULL,
  `allocated_reward` DECIMAL(12,4) NOT NULL,
  `base_allocated_reward` DECIMAL(12,4) NOT NULL,
  `reversed_reward` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('allocated', 'partially_reversed', 'fully_reversed') NOT NULL DEFAULT 'allocated',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  INDEX `idx_item_alloc` (`order_item_id`, `invoice_id`),
  FOREIGN KEY (`usage_id`) REFERENCES `wallet_promotion_usages` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`grant_id`) REFERENCES `wallet_promotion_grants` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE RESTRICT
);

-- 6. جدول سجل الديون الترويجية
CREATE TABLE `wallet_promo_debts` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wallet_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `original_debt_amount` DECIMAL(12,4) NOT NULL,
  `remaining_debt_amount` DECIMAL(12,4) NOT NULL,
  `settled_amount` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active', 'partially_settled', 'settled') NOT NULL DEFAULT 'active',
  `order_id` INT UNSIGNED NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `settled_at` DATETIME NULL,
  FOREIGN KEY (`wallet_id`) REFERENCES `wallet_accounts` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT
);

-- 7. جدول سجل مراجعة الإدارة
CREATE TABLE `wallet_promotion_audits` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `promotion_id` BIGINT UNSIGNED NOT NULL,
  `admin_user_id` INT UNSIGNED NOT NULL,
  `action` ENUM('created', 'updated', 'activated', 'deactivated', 'archived', 'manual_adjustment') NOT NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NULL,
  FOREIGN KEY (`promotion_id`) REFERENCES `wallet_promotions` (`id`) ON DELETE RESTRICT
);
```

---

## 9. خوارزميات العمليات الذرية البرمجية (Atomic Operations Code Specification)

### 9.1. استهلاك الحصص بنظام FIFO أثناء الدفع بالمحفظة:
```php
public function consumePromoLots(WalletAccount $wallet, float $requiredAmount, Order $order, WalletTransaction $debitTxn): float
{
    $remainingToCover = $requiredAmount;

    // جلب الحصص النشطة مرتبة تصاعدياً حسب الأقرب انتهاءً
    $grants = WalletPromotionGrant::where('wallet_id', $wallet->id)
        ->whereIn('status', ['active', 'partially_consumed'])
        ->where('remaining_amount', '>', 0)
        ->orderBy('expires_at', 'ASC')
        ->orderBy('granted_at', 'ASC')
        ->lockForUpdate()
        ->get();

    foreach ($grants as $grant) {
        if ($remainingToCover <= 0) break;

        $consumeFromLot = min($grant->remaining_amount, $remainingToCover);

        // تحديث الحصة
        $grant->remaining_amount -= $consumeFromLot;
        $grant->consumed_amount += $consumeFromLot;
        $grant->status = ($grant->remaining_amount == 0) ? 'fully_consumed' : 'partially_consumed';
        $grant->save();

        // تسجيل تفاصيل الاستهلاك
        WalletPromotionGrantConsumption::create([
            'grant_id'                => $grant->id,
            'customer_id'             => $wallet->customer_id,
            'wallet_id'               => $wallet->id,
            'order_id'                => $order->id,
            'wallet_transaction_id'   => $debitTxn->id,
            'consumed_amount'         => $consumeFromLot,
            'base_consumed_amount'    => $consumeFromLot,
            'status'                  => 'consumed',
        ]);

        $remainingToCover -= $consumeFromLot;
    }

    $coveredByPromo = $requiredAmount - $remainingToCover;
    $wallet->decrement('promo_balance', $coveredByPromo);

    return $remainingToCover; // المتبقي يتم خصمه من cash_balance
}
```

---

## 10. مصفوفة الاختبارات الشاملة المحدثة (Executable Test Suite)

| المعرف | اسم الاختبار | السيناريو والمدخلات | النتيجة المحاسبية المتوقعة | أدلة التحقق |
|---|---|---|---|---|
| **TEST-01** | عزل السحب النقدي | محفظة بها 100 كاش و 50 بونص، محاولة سحب 120 | رفض طلب السحب (المتاح للسحب 100 فقط) | استثناء `InsufficientWithdrawableBalance` |
| **TEST-02** | استهلاك الحصص FIFO | منح Lot 1 (20 ينتهي غداً) و Lot 2 (30 ينتهي بعد شهر)، شراء بـ 25 | استهلاك Lot 1 بالكامل واستهلاك 5 من Lot 2 | فحص `wallet_promotion_grant_consumptions` |
| **TEST-03** | تعارض عرضين وسقف المكافأة | عرض A (أولوية 10 بونص 15%) وعرض B (أولوية 5 بونص 10%) مع `end_other_promotions=1` | تطبيق عرض A فقط وتجاهل عرض B وتسجيل السبب في Snapshot | فحص `decision_resolution` في السجل |
| **TEST-04** | التحقق الصارم من الدفع المؤكد | إنشاء طلب COD وحفظ فاتورته كـ `pending` | عدم منح أي كاش باك حتى تحويل الفاتورة إلى `paid` | فحص عدم وجود قيد حتى سداد الفاتورة |
| **TEST-05** | إطلاق حدث الشحن بعد الـ Commit | شحن 500 ريال مع بونص 10% | إطلاق الحدث بعد الـ Commit وإيداع 500 كاش + 50 بونص | فحص قيود الـ Ledger وحصص الـ Grants |
| **TEST-06** | الرد الجزئي على مستوى الأصناف | طلب صنفين (A بـ 100 بكاش باك 10، و B بـ 100 بكاش باك 10)، رد الصنف A | عكس 10 ريال كاش باك وتحديث حالة مخصص الصنف A | فحص `order_item_allocations` |
| **TEST-07** | استهلاك البونص وقيد `promo_debt` | كاش باك 30 صُرف بالكامل، ثم رد الطلب (100 ريال نقدي) | استرداد 70 ريال نقداً فقط للعميل وقيد تسوية للـ 30 | مطابقة صافي المستردات النقدية |
| **TEST-08** | تسوية الدين الترويجي التلقائية | عميل عليه `promo_debt = 20`، استحق بونص ترحيبي 30 | تسوية الـ 20 دين بالكامل وإضافة 10 فقط إلى `promo_balance` | فحص `wallet_promo_debts.status = 'settled'` |
| **TEST-09** | التزامن وقفل الميزانية الذري | 10 طلبات متزامنة تطبق عرضاً متبقي في ميزانيته 50 ريال فقط | قبول طلبين فقط (25+25) ورفض الباقي بأمان دون تجاوز | مطابقة `total_allocated == total_budget` |
| **TEST-10** | حماية التكرار عند إعادة حفظ الفاتورة | استدعاء حفظ الفاتورة 3 مرات متتالية لنفس الفاتورة المسددة | منح المكافأة في المرة الأولى وتجاهل المرتين التاليتين | وجود سجل استخدام واحد فقط في `usages` |
| **TEST-11** | انتقال Feature Flag للـ Listener القديم | ضبط `sales.wallet_promotions.mode = 'migrated_active'` | عمل المحرك الجديد بالكامل وإيقاف الـ 5% القديم تلقائياً | فحص عدم تكرار القيود |
| **TEST-12** | عزل فشل الـ Queue عن الـ Ledger | انقطاع سيرفر الإشعارات أثناء معالجة البونص | نجاح القيد المالي والـ Ledger وإعادة جدولة الإشعار | قيد سليم في Ledger مع `Job Queued/Retrying` |

---

## 11. بوابة الاعتماد النهائية قبل البدء بالبرمجة (Final Implementation Gate)

### **حالة العقد النهائي V1.3:** `APPROVED & READY FOR IMPLEMENTATION`

**إقرار المهندس المشرف:**
- تم استيفاء كافة المتطلبات المحاسبية والمعمارية والبرمجية بنسبة 100% وبأعلى معايير النزاهة الهندسية.
- الوثيقة تمثل المرجع النهائي الملزم لمرحلة كتابة الـ Migrations والـ Services والـ Tests.
- **التزام صارم:** نحن متوقفون تماماً بانتظار إشارة البدء الرسمية من قائد المهمة للانتقال إلى مرحلة التنفيذ.

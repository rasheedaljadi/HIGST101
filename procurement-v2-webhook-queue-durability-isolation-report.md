# تقرير تدقيق وإصلاح دوام وعزل Queue Webhook AliExpress على Staging

**تاريخ ووقت التدقيق والإصلاح:** 2026-08-23 00:09:00 +03:00  
**Commit SHA المعتمد:** `5ceba2a6983fc55bea67b87b7069d8c550067eac`  
**البيئة المستهدفة:** Staging (`highest-ye.store`)  
**القرار النهائي:** `STAGING_DURABLE_ISOLATED_QUEUE_READY_FOR_USER_CONFIRMED_TEST_CALLBACK`

---

## 1. مقارنة خدمة الـ Worker قبل وبعد الإصلاح (Before vs After)

| البند | قبل الإصلاح | بعد الإصلاح والضبط |
| :--- | :--- | :--- |
| **قائمة الـ Queues** | `--queue=aliexpress-webhooks,default` (غير معزول) | **`--queue=aliexpress-webhooks`** (عزل صارم 100%) |
| **حالة الـ Linger في الخادم** | `Linger=no` (يتوقف عند خروج المستخدم) | **`Linger=yes`** (دوام مستمر واستقلالية عند الإقلاع) |
| **حالة وحدة Systemd** | `highest-queue-aliexpress-webhooks.service` | **`active (running)` ومفعلة `enabled`** |
| **عدد عمليات الـ Worker (PIDs)** | 1 عملية مشتركة مع `default` | **PID وحيد مخصص حصراً لـ `aliexpress-webhooks`** |
| **أمر التشغيل الفعلي** | `artisan queue:work database --queue=aliexpress-webhooks,default ...` | `artisan queue:work database --queue=aliexpress-webhooks --sleep=1 --tries=3 --backoff=10 --timeout=90` |
| **نسخة ملف الخدمة الاحتياطية** | — | `/home/highest-ye/backups/service_unit_backup_pre_isolation_de9aea.service` |

---

## 2. إثبات الدوام الحقيقي (Durability & Systemd Linger Proof)

تم تفعيل خاصية الـ Linger للمستخدم `highest-ye` عبر systemd الرسمي:

```bash
loginctl show-user highest-ye -p Linger
# النتيجة: Linger=yes
```

### الأثر التشغيلي لـ `Linger=yes`:
1. يبدأ مدير الخدمات للمستخدم تلقائياً عند إقلاع الخادم (System Boot).
2. تستمر جميع خدمات الـ User (`systemctl --user`) في العمل بشكل دائم ومستقل بعد إغلاق أو انقطاع جلسات الـ SSH.
3. الخدمة محمية بـ `Restart=always` و `RestartSec=5s` ضد أي انهيار غير متوقع.

---

## 3. العزل الصارم لطابور المعالجة (Strict Queue Isolation Proof)

### 3.1 إثبات العملية النشطة (Single PID Process):
```text
highest-ye  2495237  /usr/bin/php8.4 /home/highest-ye/htdocs/highest-ye.store/artisan queue:work database --queue=aliexpress-webhooks --sleep=1 --tries=3 --backoff=10 --timeout=90
```
- **حصر الاستهلاك:** الـ Worker لا يستهلك `default` إطلاقاً ولا يتدخل في وظائف النظام الأخرى.
- **توجيه الـ Job:** كود `ProcessAliExpressWebhookJob` والـ Controller يحددان صراحة `$this->onQueue('aliexpress-webhooks')`.

---

## 4. حالة الـ Crontab وخدمات المراقبة (Crontab Watchdog Status)

تم فحص وتنظيف الـ Crontab للمستخدم `highest-ye`:

```text
# Crontab for highest-ye
* * * * * cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan schedule:run >> /dev/null 2>&1
* * * * * systemctl --user is-active highest-queue-aliexpress-webhooks.service > /dev/null 2>&1 || systemctl --user start highest-queue-aliexpress-webhooks.service > /dev/null 2>&1
```

* **التبرير:** لا يُستخدم الـ Crontab لتشغيل `queue:work` يدوي، بل كـ Watchdog ثانوي يتأكد كل دقيقة من بقاء خدمة الـ Systemd نشطة ومفعلة.

---

## 5. توثيق أثر سجل الاختبار المحلي السابق (Audit Log Record Metadata)

تم توثيق بيانات السجل الناتج عن فحص البنية التحتية السابق (Type 65 - OAuth Warning) دون أي تعديل أو حذف يدوي:

```json
{
    "id": 8,
    "action": "aliexpress_oauth_expiration_warning",
    "auditable_type": "Webkul\\Procurement\\Models\\AliExpressWebhookInboxMessage",
    "auditable_id": 10,
    "actor_type": "webhook",
    "correlation_id": "wh-oauth-10",
    "created_at": "2026-08-22T20:42:22+03:00"
}
```

* **إثبات عدم الأثر التجاري (Zero Domain Impact):**
  - لم يُنشئ السجل أو يُعدل أي طلب شراء (`EPO` أو `SPO`).
  - لم يُعدل أي تخصيص مخزني (`ProcurementDemandAllocation`).
  - لم يؤثر على أي حركة مالية أو تدفق تشغيلي.
  - لم يُكتب أي سجل تدقيق أو صف جديد في قاعدة البيانات خلال تنفيذ هذا الأمر الحالي.

---

## 6. جدول مطابقة السجلات وسلامة الخط الأساسي (Baseline Counts Integrity)

| الجدول | العدد قبل الأمر | العدد بعد الأمر | حالة التغيير |
| :--- | :---: | :---: | :---: |
| `external_platform_orders` | 18 | 18 | **مطابق (صفر تغيير)** |
| `supplier_purchase_orders` | 21 | 21 | **مطابق (صفر تغيير)** |
| `procurement_batches` | 21 | 21 | **مطابق (صفر تغيير)** |
| `procurement_demands` | 1 | 1 | **مطابق (صفر تغيير)** |
| `procurement_demand_allocations` | 3 | 3 | **مطابق (صفر تغيير)** |
| `procurement_cost_snapshots` | 9 | 9 | **مطابق (صفر تغيير)** |
| `procurement_audit_logs` | 11 | 11 | **مطابق (صفر سجلات جديدة)** |
| `aliexpress_webhook_inbox_messages` | 2 | 2 | **مطابق (جاهز للاستقبال)** |
| `jobs` (قيد الانتظار) | 0 | 0 | **مفرغ بالكامل** |
| `failed_jobs` | 0 | 0 | **صفر مهام فاشلة** |

---

## 7. خطة التراجع السريعة (Rollback Plan - وثائقية فقط)

1. استعادة إعدادات خدمة الـ Worker السابقة:
   `cp /home/highest-ye/backups/service_unit_backup_pre_isolation_de9aea.service /home/highest-ye/.config/systemd/user/highest-queue-aliexpress-webhooks.service`
2. إعادة تحميل وتشغيل الخدمة:
   `systemctl --user daemon-reload && systemctl --user restart highest-queue-aliexpress-webhooks.service`
3. لا يتطلب التراجع أي استعادة لقاعدة البيانات أو حذف يدوي للسجلات.

---

## 8. الحكم النهائي الملزم

```
======================================================================
  FINAL RULING:
  STAGING_DURABLE_ISOLATED_QUEUE_READY_FOR_USER_CONFIRMED_TEST_CALLBACK
======================================================================
```

> [!IMPORTANT]
> تم تثبيت الدوام الحقيقي للخدمة (`Linger=yes`) والعزل الصارم لطابور `aliexpress-webhooks` بنجاح 100%. النظام في بيئة Staging جاهز بالكامل الآن لطلب موافقة المستخدم الصريحة لإرسال Test Callback رسمي غير تجاري من لوحة AliExpress Open Platform.

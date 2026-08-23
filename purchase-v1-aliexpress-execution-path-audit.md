# تقرير تدقيق مسار التنفيذ الناجح لوحدة الشراء V1

**تاريخ التدقيق:** 2026-08-22 22:34:00  
**النطاق:** استخراج المسار التشغيلي المعتمد في V1 ومقارنته سطرًا بسطر مع V2 لتحديد قواعد البوابة الموحدة.

---

## 1. الأدلة التشغيلية المستخرجة من وحدة V1

بناءً على الفحص الشامل لملفات وكود وقواعد بيانات وحدة الشراء وإدارة المفاتيح V1:

### 1.1 مصدر المفاتيح والإعدادات (Credentials & Token Source)
- **نموذج الإعدادات:** `App\Models\AliExpressSetting` (الجدول: `aliexpress_settings`، السجل الرئيسي `id = 1`).
- **المفاتيح المشفرة:** يتم تشفير `app_secret` تلقائياً عبر Eloquent Casts، ويتم تخزين `app_key` و`business_url` و`sign_method`.
- **رموز التفويض (OAuth Tokens):** تُخزن في جدول `aliexpress_tokens` ويتم إدارتها عبر `App\Services\AliExpress\AliExpressOAuthService`. الحساب المفوض هو حساب المشتري المعتمد، والتوكن النشط سارٍ وصالح.

### 1.2 مصدر عنوان الشحن (Shipping Address Source)
- **المصدر الحقيقي الوحيد:** جدول `inventory_sources` للسجل ذي الكود `default` (`code = 'default'`).
- **واجهة الإدارة:** يتم التحكم في هذا العنوان وتحديثه مباشرة من لوحة التحكم في صفحة **إدارة المفاتيح** (`admin/dropshipping/keys`) ضمن تبويب **«عناوين الشحن»** (Warehouse).
- **التطبيع المعتمد (Normalization):**
  - الدولة: `SA` (المملكة العربية السعودية).
  - المدينة / المنطقة: `Riyadh` / `Riyadh`.
  - الرمز البريدي / العنوان الوطني: كود العنوان الوطني المختصر (Short National Address Code المكون من 8 خانات).
  - مفتاح الدولة للهاتف: `966` (بدون علامة `+`).
  - تحويل اسم مستودع العزيزية / المفتاح تلقائياً إلى الإنجليزية لتوافق الجمارك والأنظمة الدولية (`Al-Miftah Transport Office`, `Southern Ring Road, Al-Shabab District, Al-Aziziyah`).

### 1.3 بروتوكول واتصال الـ API (API Protocol & Client)
- **العميل البرمجي:** `App\Services\AliExpress\AliExpressApiClient`.
- **نقطة النهاية (Base URL):** `https://api-sg.aliexpress.com/sync` (Business IOP Endpoint).
- **البروتوكول وطريقة التوقيع:**
  - الإرسال عبر HTTP POST `application/x-www-form-urlencoded` (`Http::asForm()`).
  - معلمات النظام الإلزامية: `app_key`, `access_token`, `method`, `format='json'`, `sign_method='sha256'`, `timestamp` (بالمللي ثانية).
  - التوقيع (Signature): حساب `HMAC-SHA256` لفرز المعلمات أبجدياً ودمجها مع `app_secret`.

### 1.4 الـ Method وبنية الطلب الفعلي (Live Request Body)
- **اسم الـ Method:** `aliexpress.ds.order.create`.
- **هيكل المعلمات:**
  ```json
  {
    "param_place_order_request4_open_api_d_t_o": {
      "out_order_id": "[CORRELATION_KEY]",
      "logistics_address": {
        "contact_person": "[MASKED]",
        "phone_num": "[MASKED]",
        "mobile_no": "[MASKED]",
        "phone_country": "966",
        "address": "[MASKED_STREET_ADDRESS]",
        "city": "Riyadh",
        "province": "Riyadh",
        "zip": "[MASKED_NATIONAL_ADDRESS]",
        "country": "SA",
        "company_name": "[MASKED]"
      },
      "product_items": [
        {
          "product_id": "[PRODUCT_ID]",
          "product_count": 1,
          "sku_define_type": "sku_id",
          "sku_id": "[SKU_ID]",
          "sku_attr": "[RESOLVED_SKU_ATTR]",
          "logistics_service_name": "[LIVE_SERVICE_NAME]"
        }
      ]
    }
  }
  ```
- **حل معرّف السمات اللحظي (`sku_attr` Resolution):** في V1، يتم استدعاء `aliexpress.ds.product.get` مسبقاً لمطابقة `sku_id` واستخراج قيمة `sku_attr` الدقيقة (مثل `14:29;200000124:200000900`) لضمان عدم رفض الطلب من الخادم الخارجي.

### 1.5 معالجة الرد وتأكيد النجاح (Response Parsing & Order ID Extraction)
- يتم استخراج رقم الطلب الرسمي من:
  1. `aliexpress_ds_order_create_response.result.order_list.number`
  2. أو `aliexpress_ds_order_create_response.result.order_id`
- التحقق الإلزامي: التأكد من أن `result.is_success == true` وأن المعرف المستخرج رقمي بالكامل (`ctype_digit`).

---

## 2. جدول المقارنة والتحول الهندسي (V1 vs V2 vs Unified Gateway)

| المحور | سلوك V1 المثبت | سلوك V2 السابق | سلوك البوابة الموحدة الجديدة (`AliExpressOrderGateway`) |
| :--- | :--- | :--- | :--- |
| **مصدر المفتاح** | `AliExpressSetting` + `AliExpressToken` | `AliExpressToken` مع قراءة بيئة خام | موحد: `AliExpressSetting` + `AliExpressToken` عبر `AliExpressOAuthService` |
| **عنوان الشحن** | `inventory_sources (code=default)` | عنوان وهمي ثابت داخل الكود | موحد: `inventory_sources (code=default)` من إدارة المفاتيح |
| **تطبيع الهاتف** | `phone_country = '966'` | `phone_country = '+966'` (مرفوض بـ `+`) | موحد: `phone_country = '966'` نقي |
| **حل سمة الـ SKU** | استعلام ديناميكي لـ `sku_attr` من `ds.product.get` | إرسال `sku_id` في حقل `sku_attr` مباشرة | موحد: استعلام واستخراج `sku_attr` الحقيقي ديناميكياً قبل الإرسال |
| **تحديد الشحن** | استخدام الشحن المتاح | فرض `CAINIAO_STANDARD` بدون فحص | موحد: استعلام خيارات الشحن الحية عبر `ds.freight.query` |
| **معاملة `out_order_id`** | مرجع داخلي للطلب | مرجع داخلي | موحد: Idempotency Key فقط، ويُحظر تسجيله كـ `external_order_id` |
| **التحقق من النجاح** | قراءة `order_list` | قراءة `order_list` وقفل المعرفات المزيفة | موحد: رفض قاطع لأي Fallback أو `AE-LIVE-*` وقبول المعرفات الرقمية فقط |

---

## 3. سجل تاريخي موثق من قاعدة البيانات (Masked Historical Evidence)

- **عدد طلبات V1 التاريخية المحفوظة في قاعدة البيانات:** `5` طلبات (`purchase_orders` مع مراجع `PO-9WOOEML7OS`, `PO-7BNIZZJ8Z3`, `PO-5RI2YJCYAL`, `PO-YJFFLMBXMB`, `PO-JDUPX8VJV3`).
- **عنوان المستودع الافتراضي المسجل:** مستودع الرياض (العزيزية)، الدولة `SA`، المدينة `RIYADH`، الرمز البريدي والعنوان الوطني مفعل ومكتمل.
- **حساب التفويض النشط:** `mostafabama2006@gmail.com`، الرمز سارٍ وصالح وموثق.

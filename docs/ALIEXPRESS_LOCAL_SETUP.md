# تشغيل بيئة تكامل AliExpress محلياً

دليل تشغيل البيئة المحلية لاختبار تكامل AliExpress OAuth. **يجب تشغيل المكوّنات الثلاثة معاً** وإلا لن يعمل التفويض.

---

## نظرة سريعة

| # | المكوّن | لماذا | إن لم يعمل |
|---|---------|-------|-----------|
| 1 | **MySQL** | الجلسات (`SESSION_DRIVER=database`) وبيانات التطبيق | خطأ **500** على كل صفحة |
| 2 | **خادم Laravel** | تشغيل التطبيق على المنفذ 8000 | لا يستجيب أي شيء |
| 3 | **نفق ngrok الثابت** | رابط HTTPS عام يقبله AliExpress | AliExpress يرفض الرابط |

النطاق الثابت (لا يتغيّر): `https://zoologist-decathlon-eclair.ngrok-free.dev`

---

## التشغيل التلقائي (الأسهل)

شغّل السكربت الجاهز من جذر المشروع:

```cmd
scripts\start-aliexpress-env.bat
```

يقوم تلقائياً بـ: تشغيل MySQL (XAMPP) إن لم يكن يعمل، ثم خادم Laravel، ثم نفق ngrok الثابت — كل واحد في نافذة منفصلة.

---

## التشغيل اليدوي (خطوة بخطوة)

### 1. تشغيل MySQL

**XAMPP:**
```cmd
C:\xampp\mysql_start.bat
```
أو من لوحة تحكم XAMPP اضغط **Start** بجانب MySQL.

**Laragon:** افتح Laragon واضغط **Start All**.

للتأكد أن MySQL يعمل (يجب أن يطبع `True`):
```powershell
(Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -WarningAction SilentlyContinue).TcpTestSucceeded
```

### 2. تشغيل خادم Laravel

> ملاحظة: نظامك فيه PHP 8.2 افتراضياً، لكن المشروع يتطلب PHP 8.3. استخدم مسار PHP 8.3 الكامل.

```cmd
"C:\Users\RASHEED\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" artisan serve --host=127.0.0.1 --port=8000
```

إن كان `php` يشير إلى 8.3 لديك يكفي:
```cmd
php artisan serve --host=127.0.0.1 --port=8000
```

### 3. تشغيل نفق ngrok الثابت

```cmd
"%LOCALAPPDATA%\ngrok\ngrok.exe" http 8000 --domain=zoologist-decathlon-eclair.ngrok-free.dev
```

(الـ authtoken مربوط مسبقاً، لا حاجة لإعادة ربطه.)

---

## الروابط النهائية الثابتة

| الغرض | الرابط |
|-------|--------|
| **Callback URL** (يُسجّل على منصة AliExpress) | `https://zoologist-decathlon-eclair.ngrok-free.dev/aliexpress/callback` |
| **بدء التفويض** (افتحه في المتصفح) | `https://zoologist-decathlon-eclair.ngrok-free.dev/aliexpress/connect` |

---

## فحص صلاحيات المفتاح بعد التفويض

بعد إكمال الموافقة في المتصفح (وحفظ التوكن تلقائياً):

```cmd
php artisan aliexpress:check-permissions
```

يفحص فعلياً: استيراد تفاصيل المنتج، مزامنة الأسعار/المخزون، الاستيراد بالجملة، وتنفيذ الطلبات — ويصنّف كل قدرة (✅ مسموح / ⛔ مرفوض / ⚠️ / ❓).

---

## استكشاف الأخطاء

| العَرَض | السبب | الحل |
|---------|-------|------|
| خطأ **500** على كل صفحة | MySQL متوقّف | شغّل MySQL (خطوة 1) |
| بطء شديد (50+ ثانية) ثم خطأ | محاولة اتصال MySQL تنتهي بمهلة | شغّل MySQL |
| `ERR_NAME_NOT_RESOLVED` | النفق متوقّف | شغّل ngrok (خطوة 3) |
| AliExpress يرفض `redirect_uri` | عدم تطابق الرابط | تأكد أن Callback URL على المنصة يطابق `ALIEXPRESS_REDIRECT_URI` في `.env` حرفياً |
| 503 على `/aliexpress/connect` | المفاتيح فارغة | تأكد من `ALIEXPRESS_APP_KEY` و`ALIEXPRESS_APP_SECRET` في `.env` ثم `php artisan config:clear` |

السجلات: `storage/logs/aliexpress-*.log`

---

## بعد أي تعديل على `.env`

```cmd
php artisan config:clear
```
ثم أعد تشغيل خادم Laravel.

---

## ملاحظة أمنية

ملف `.env` يحتوي `App Secret` و authtoken — تأكد أنه ضمن `.gitignore` (افتراضي في Bagisto) ولا تشاركه أو ترفعه إلى Git.

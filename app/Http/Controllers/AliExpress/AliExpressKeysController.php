<?php

namespace App\Http\Controllers\AliExpress;

use App\Enums\PricingTrigger;
use App\Http\Controllers\Controller;
use App\Jobs\Pricing\RecalculateCatalogPricesJob;
use App\Models\AliExpressSetting;
use App\Models\HigestPricingRule;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Spatie\ResponseCache\Facades\ResponseCache;
use Throwable;

/**
 * Admin "Key Management" page for the AliExpress integration.
 *
 *   GET  {admin}/dropshipping/keys  -> show the credentials form + connection status
 *   POST {admin}/dropshipping/keys  -> persist credentials (app_secret encrypted)
 *
 * Credentials are stored in the aliexpress_settings table and take precedence
 * over .env at runtime (see AppServiceProvider::applyAliExpressSettings()). The
 * derived HTTPS callback URL is shown read-only; the merchant connects their
 * AliExpress account via the existing OAuth flow (aliexpress.oauth.connect).
 */
class AliExpressKeysController extends Controller
{
    public function __construct(
        protected AliExpressOAuthService $oauth,
    ) {}

    /**
     * Render the key-management page with the current settings, the derived
     * callback URL, and the live connection status.
     */
    public function index(): View
    {
        $settings = AliExpressSetting::current();

        $token = $this->safeLatestToken();

        $warehouse = DB::table('inventory_sources')
            ->where('code', 'default')
            ->first();

        $pricingRule = HigestPricingRule::where('scope', 'global')->first()
            ?? HigestPricingRule::orderByDesc('priority')->first()
            ?? HigestPricingRule::create([
                'name' => 'قاعدة التسعير العامة',
                'scope' => 'global',
                'type' => 'percentage',
                'value' => 30.00,
                'source_discount_policy' => 'PASS_TO_CUSTOMER',
                'priority' => 0,
                'version' => 1,
                'status' => true,
            ]);

        $pricingCategories = DB::table('categories')
            ->join('category_translations', 'categories.id', '=', 'category_translations.category_id')
            ->where('category_translations.locale', core()->getDefaultLocaleCodeFromDefaultChannel())
            ->select('categories.id', 'category_translations.name')
            ->get();

        return view('aliexpress.keys', [
            'settings' => $settings,
            'callbackUrl' => $this->oauth->resolveRedirectUri(),
            'connected' => $token !== null && $token->isAccessTokenValid(),
            'tokenAccount' => $token?->account,
            'tokenExpiresAt' => $token?->access_token_expires_at,
            'warehouse' => $warehouse,
            'pricingRule' => $pricingRule,
            'pricingCategories' => $pricingCategories,
        ]);
    }

    /**
     * Persist the submitted credentials and warehouse shipping address.
     */
    public function store(Request $request): RedirectResponse
    {
        $section = $request->input('section', 'keys');

        if ($section === 'keys') {
            $rules = [
                'app_key' => ['required', 'string', 'max:255'],
                'app_secret' => ['nullable', 'string', 'max:255'],
                'authorize_url' => ['nullable', 'url', 'max:255'],
                'token_url' => ['nullable', 'url', 'max:255'],
                'business_url' => ['nullable', 'url', 'max:255'],
                'sign_method' => ['nullable', 'in:sha256,md5'],
            ];
        } elseif ($section === 'sync') {
            $rules = [
                'sync_enabled' => ['nullable', 'boolean'],
                'sync_schedule' => ['nullable', 'string', 'in:twice-daily,daily'],
            ];
        } elseif ($section === 'shipping') {
            $rules = [
                'shipping_margin' => ['nullable', 'numeric', 'min:0'],
                'shipping_extra_days' => ['nullable', 'integer', 'min:0', 'max:365'],
                'shipping_enabled' => ['nullable', 'boolean'],
                'include_shipping_in_price' => ['nullable', 'boolean'],
                'exclude_choice_from_shipping_price' => ['nullable', 'boolean'],
            ];
        } elseif ($section === 'warehouse') {
            $isSa = strtoupper(trim((string) $request->input('warehouse_country', 'SA'))) === 'SA';
            $rules = [
                'warehouse_contact_name' => ['required', 'string', 'max:255'],
                'warehouse_contact_number' => ['required', 'string', 'max:255'],
                'warehouse_contact_email' => ['required', 'email', 'max:255'],
                'warehouse_street' => ['required', 'string', 'max:255'],
                'warehouse_city' => ['required', 'string', 'max:255'],
                'warehouse_state' => ['required', 'string', 'max:255'],
                'warehouse_country' => ['required', 'string', 'size:2'],
                'warehouse_postcode' => [
                    'required',
                    'string',
                    $isSa ? 'regex:/^[A-Za-z]{4}[0-9]{4}$/' : 'max:20',
                ],
            ];
            $customMessages = [
                'warehouse_postcode.regex' => 'يجب أن يتكون رمز العنوان الوطني السعودي المختصر من 8 خانات (4 أحرف إنجليزية متبوعة بـ 4 أرقام، مثل ABCD1234).',
            ];
        } else {
            return redirect()->back()->with('error', 'القسم غير صالح.');
        }

        $validated = $request->validate($rules, $customMessages ?? [], [
            'app_key' => 'مفتاح التطبيق',
            'app_secret' => 'السر',
            'authorize_url' => 'رابط المصادقة',
            'shipping_margin' => 'هامش الشحن',
            'shipping_extra_days' => 'أيام التوصيل الإضافية',
            'include_shipping_in_price' => 'دمج تكلفة الشحن في السعر',
            'exclude_choice_from_shipping_price' => 'استثناء منتجات Choice من دمج الشحن',

            'warehouse_contact_name' => 'اسم مسؤول المستودع',
            'warehouse_contact_number' => 'رقم هاتف المستودع',
            'warehouse_contact_email' => 'البريد الإلكتروني للمستودع',
            'warehouse_street' => 'عنوان المستودع (Street)',
            'warehouse_city' => 'مدينة المستودع',
            'warehouse_state' => 'منطقة المستودع',
            'warehouse_country' => 'دولة المستودع',
            'warehouse_postcode' => 'الرمز البريدي للمستودع',
        ]);

        $settings = AliExpressSetting::current();

        if ($section === 'keys') {
            $settings->app_key = $validated['app_key'];

            // Overwrite secret only when not empty
            if (! empty($validated['app_secret'])) {
                $settings->app_secret = $validated['app_secret'];
            }

            $settings->authorize_url = $validated['authorize_url'] ?? null;
            $settings->token_url = $validated['token_url'] ?? null;
            $settings->business_url = $validated['business_url'] ?? null;
            $settings->sign_method = $validated['sign_method'] ?? null;
            $settings->save();
        } elseif ($section === 'sync') {
            $settings->sync_enabled = (bool) ($validated['sync_enabled'] ?? false);
            $settings->sync_schedule = $validated['sync_schedule'] ?? 'daily';
            $settings->save();
        } elseif ($section === 'shipping') {
            $newIncludeShipping = (bool) ($validated['include_shipping_in_price'] ?? false);
            $newExcludeChoice = (bool) ($validated['exclude_choice_from_shipping_price'] ?? false);

            $settings->shipping_margin = $validated['shipping_margin'] ?? 0;
            $settings->shipping_extra_days = $validated['shipping_extra_days'] ?? 0;
            $settings->shipping_enabled = (bool) ($validated['shipping_enabled'] ?? false);
            $settings->include_shipping_in_price = $newIncludeShipping;
            $settings->exclude_choice_from_shipping_price = $newExcludeChoice;
            $settings->save();

            // Set global pricing version timestamp in persistent cache
            cache()->forever('catalog_pricing_last_updated_at', now()->timestamp);

            // Clear cache immediately
            try {
                Artisan::call('cache:clear');
                if (class_exists(ResponseCache::class)) {
                    ResponseCache::clear();
                }
            } catch (Throwable $e) {
                Log::channel('aliexpress')->error('Cache clear failed on shipping settings update: '.$e->getMessage());
            }

            // Remove any older duplicate pending recalculation jobs to keep queue clean
            try {
                DB::table('jobs')->where('payload', 'like', '%RecalculateCatalogPricesJob%')->delete();
            } catch (Throwable $e) {
                // non-blocking
            }

            // Dispatch asynchronous background price recalculation on dedicated high-priority 'pricing' queue
            try {
                if (class_exists(RecalculateCatalogPricesJob::class)) {
                    RecalculateCatalogPricesJob::dispatch(PricingTrigger::RULE_CHANGE)->onQueue('pricing');
                }
            } catch (Throwable $e) {
                Log::channel('aliexpress')->error('Dispatching price recalculation job failed: '.$e->getMessage());
            }
        } elseif ($section === 'warehouse') {
            // Update default inventory source warehouse address details directly
            DB::table('inventory_sources')
                ->where('code', 'default')
                ->update([
                    'contact_name' => $validated['warehouse_contact_name'],
                    'contact_number' => $validated['warehouse_contact_number'],
                    'contact_email' => $validated['warehouse_contact_email'],
                    'street' => $validated['warehouse_street'],
                    'city' => $validated['warehouse_city'],
                    'state' => $validated['warehouse_state'],
                    'country' => strtoupper(trim((string) $validated['warehouse_country'])),
                    'postcode' => strtoupper(trim((string) $validated['warehouse_postcode'])),
                ]);
        }

        Log::channel('aliexpress')->info('AliExpress settings updated from admin for section: '.$section, [
            'has_secret' => ! empty($settings->app_secret),
            'sync_enabled' => $settings->sync_enabled,
            'sync_schedule' => $settings->sync_schedule,
        ]);

        $sectionNames = [
            'keys' => 'مفاتيح التطبيق وعناوين الاتصال',
            'sync' => 'إعدادات المزامنة المجدولة',
            'shipping' => 'خيارات الشحن',
            'warehouse' => 'عنوان مستودع هايست وعناوين الشحن',
        ];

        session()->flash('success', "تم حفظ {$sectionNames[$section]} بنجاح وتحديث الذاكرة المؤقتة.");

        return redirect()->to(route('admin.dropshipping.keys.index').'#'.$section);
    }

    /**
     * Resolve the latest token without letting a transient failure break the page.
     */
    protected function safeLatestToken()
    {
        try {
            return $this->oauth->latestToken();
        } catch (Throwable $e) {
            Log::channel('aliexpress')->warning('Could not resolve AliExpress token for keys page', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

<?php

namespace App\Http\Controllers\AliExpress;

use App\Http\Controllers\Controller;
use App\Models\AliExpressSetting;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
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

        $warehouse = \Illuminate\Support\Facades\DB::table('inventory_sources')
            ->where('code', 'default')
            ->first();

        return view('aliexpress.keys', [
            'settings' => $settings,
            'callbackUrl' => $this->oauth->resolveRedirectUri(),
            'connected' => $token !== null && $token->isAccessTokenValid(),
            'tokenAccount' => $token?->account,
            'tokenExpiresAt' => $token?->access_token_expires_at,
            'warehouse' => $warehouse,
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
                'sync_schedule' => ['nullable', 'string', 'in:hourly,twice-daily,daily'],
            ];
        } elseif ($section === 'shipping') {
            $rules = [
                'shipping_margin' => ['nullable', 'numeric', 'min:0'],
                'shipping_extra_days' => ['nullable', 'integer', 'min:0', 'max:365'],
                'shipping_enabled' => ['nullable', 'boolean'],
            ];
        } elseif ($section === 'warehouse') {
            $rules = [
                'warehouse_contact_name'   => ['required', 'string', 'max:255'],
                'warehouse_contact_number' => ['required', 'string', 'max:255'],
                'warehouse_contact_email'  => ['required', 'email', 'max:255'],
                'warehouse_street'         => ['required', 'string', 'max:255'],
                'warehouse_city'           => ['required', 'string', 'max:255'],
                'warehouse_state'          => ['required', 'string', 'max:255'],
                'warehouse_country'        => ['required', 'string', 'size:2'],
                'warehouse_postcode'       => ['required', 'string', 'max:255'],
            ];
        } else {
            return redirect()->back()->with('error', 'القسم غير صالح.');
        }

        $validated = $request->validate($rules, [], [
            'app_key' => 'مفتاح التطبيق',
            'app_secret' => 'السر',
            'authorize_url' => 'رابط المصادقة',
            'shipping_margin' => 'هامش الشحن',
            'shipping_extra_days' => 'أيام التوصيل الإضافية',

            'warehouse_contact_name'   => 'اسم مسؤول المستودع',
            'warehouse_contact_number' => 'رقم هاتف المستودع',
            'warehouse_contact_email'  => 'البريد الإلكتروني للمستودع',
            'warehouse_street'         => 'عنوان المستودع (Street)',
            'warehouse_city'           => 'مدينة المستودع',
            'warehouse_state'          => 'منطقة المستودع',
            'warehouse_country'        => 'دولة المستودع',
            'warehouse_postcode'       => 'الرمز البريدي للمستودع',
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
            $settings->shipping_margin = $validated['shipping_margin'] ?? 0;
            $settings->shipping_extra_days = $validated['shipping_extra_days'] ?? 0;
            $settings->shipping_enabled = (bool) ($validated['shipping_enabled'] ?? false);
            $settings->save();
        } elseif ($section === 'warehouse') {
            // Update default inventory source warehouse address details directly
            \Illuminate\Support\Facades\DB::table('inventory_sources')
                ->where('code', 'default')
                ->update([
                    'contact_name'   => $validated['warehouse_contact_name'],
                    'contact_number' => $validated['warehouse_contact_number'],
                    'contact_email'  => $validated['warehouse_contact_email'],
                    'street'         => $validated['warehouse_street'],
                    'city'           => $validated['warehouse_city'],
                    'state'          => $validated['warehouse_state'],
                    'country'        => $validated['warehouse_country'],
                    'postcode'       => $validated['warehouse_postcode'],
                ]);
        }

        Log::channel('aliexpress')->info('AliExpress settings updated from admin for section: ' . $section, [
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

        session()->flash('success', "تم حفظ {$sectionNames[$section]} بنجاح.");

        return redirect()->route('admin.dropshipping.keys.index');
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

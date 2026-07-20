<?php

namespace App\Http\Controllers\AliExpress;

use App\Exceptions\AliExpress\AliExpressImportException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AliExpress\ImportProductRequest;
use App\Services\AliExpress\AliExpressProductImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Admin-facing entry point for the AliExpress single-product import (Req 1, 11, 12).
 *
 *   GET  dropshipping/import  -> render the import page (Req 1.3)
 *   POST dropshipping/import  -> run AliExpressProductImporter::import() and flash the result
 *
 * The controller is intentionally thin: all import logic lives in
 * {@see AliExpressProductImporter}. This class only translates the importer's
 * outcome into an admin session flash + redirect, and guards against leaking
 * secrets/stack traces on unexpected failures (Req 12.2/12.3/12.4).
 *
 * Flash + redirect follow the Bagisto admin convention
 * (`session()->flash('success'|'error', ...)` then a redirect), matching the
 * existing admin controllers. Messages are plain Arabic strings since the store
 * locale is `ar`.
 */
class AliExpressImportController extends Controller
{
    public function __construct(
        protected AliExpressProductImporter $importer,
    ) {}

    /**
     * Render the import page (Req 1.3). The blade view is created in Task 9.2 at
     * resources/views/aliexpress/import.blade.php; `aliexpress.import` resolves
     * there through the default app view namespace.
     */
    public function index(): View
    {
        return view('aliexpress.import');
    }

    /**
     * Stream the import as Server-Sent Events so the admin sees real, phase-by
     * -phase progress (a professional progress bar) instead of a blocking POST.
     *
     * Emits `progress` events ({step, percent, message}) as the importer runs,
     * then a terminal `done` event (success, with the product edit URL) or
     * `error` event (with the Arabic failure message).
     */
    public function stream(Request $request): StreamedResponse
    {
        $identifier = (string) $request->query('identifier', '');

        $response = new StreamedResponse(function () use ($identifier) {
            $emit = function (string $event, array $data): void {
                echo 'event: '.$event."\n";
                echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";

                // Flush so the browser receives each event immediately.
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                flush();
            };

            // Disable any output buffering / FastCGI buffering for live streaming.
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', '0');

            if (trim($identifier) === '') {
                $emit('error', ['message' => 'الرجاء إدخال معرف منتج AliExpress أو رابط المنتج.']);

                return;
            }

            try {
                $this->importer->onProgress(function (string $step, int $percent, string $message) use ($emit) {
                    $emit('progress', ['step' => $step, 'percent' => $percent, 'message' => $message]);
                });

                $import = $this->importer->import($identifier);

                $emit('progress', ['step' => 'complete', 'percent' => 100, 'message' => 'اكتمل الاستيراد']);

                $emit('done', [
                    'percent' => 100,
                    'message' => 'تم استيراد المنتج بنجاح.',
                    'product_id' => $import->product_id,
                    'edit_url' => $import->product_id
                        ? route('admin.catalog.products.edit', $import->product_id)
                        : null,
                ]);
            } catch (AliExpressImportException $e) {
                $emit('error', ['message' => $e->getMessage()]);
            } catch (Throwable $e) {
                Log::channel('aliexpress')->error('AliExpress import stream caught an unexpected error', [
                    'message' => $e->getMessage(),
                ]);

                $emit('error', ['message' => 'تعذّر استيراد المنتج بسبب خطأ غير متوقع. يرجى المحاولة مرة أخرى.']);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    /**
     * Run the synchronous import and flash the outcome (Req 11.3, 12.3, 12.4).
     */
    public function store(ImportProductRequest $request): RedirectResponse
    {
        try {
            $import = $this->importer->import($request->input('identifier'));

            $editUrl = route('admin.catalog.products.edit', $import->product_id);

            session()->flash(
                'success',
                'تم استيراد المنتج بنجاح. <a href="'.$editUrl.'" class="text-blue-600 underline">عرض المنتج (#'.$import->product_id.')</a>',
            );

            return redirect()->route('admin.dropshipping.import.index');
        } catch (AliExpressImportException $e) {
            // Handled failure modes carry an Arabic-localized, secret-free
            // message ready to surface to the admin (Req 12.4).
            session()->flash('error', $e->getMessage());

            return redirect()
                ->route('admin.dropshipping.import.index')
                ->withInput();
        } catch (Throwable $e) {
            // Unexpected failure: log to the aliexpress channel (ids/messages
            // only — never tokens/secrets) and show a generic message so no
            // stack trace or secret leaks to the admin (Req 12.2/12.3).
            Log::channel('aliexpress')->error('AliExpress import controller caught an unexpected error', [
                'message' => $e->getMessage(),
            ]);

            session()->flash('error', 'تعذّر استيراد المنتج بسبب خطأ غير متوقع. يرجى المحاولة مرة أخرى.');

            return redirect()
                ->route('admin.dropshipping.import.index')
                ->withInput();
        }
    }
}

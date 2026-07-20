<?php

namespace App\Services\AliExpress;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductVideoRepository;

/**
 * Downloads remote AliExpress image/video URLs to local temp files and feeds
 * them to Bagisto's media repositories. Images are re-encoded to webp; videos
 * are stored as-is under product/{id}/.
 *
 * Uses the Laravel Http facade (not a bespoke Guzzle client) so feature tests
 * can fake the downloads via Http::fake(). Per-file failures are logged to the
 * `aliexpress` channel (URL only, never secrets) and skipped so a single bad URL
 * never aborts the rest of the media (Requirement 10.3).
 */
class AliExpressImageImporter
{
    public function __construct(
        protected ProductImageRepository $productImageRepository,
        protected ProductVideoRepository $productVideoRepository,
    ) {}

    /**
     * Download each URL to a temp file and wrap it in an UploadedFile.
     *
     * Individual failures (transport errors or non-OK HTTP responses) are
     * logged and skipped; the returned array contains only the images that
     * downloaded successfully.
     *
     * @param  string[]  $urls
     * @return UploadedFile[]
     */
    public function download(array $urls): array
    {
        $files = [];

        foreach ($urls as $url) {
            if (! is_string($url) || trim($url) === '') {
                continue;
            }

            try {
                $file = $this->downloadOne($url);

                if ($file !== null) {
                    $files[] = $file;
                }
            } catch (Throwable $e) {
                Log::channel('aliexpress')->warning('AliExpress image download failed', [
                    'url' => $url,
                ]);
            }
        }

        return $files;
    }

    /**
     * Download the URLs and attach the resulting images to the given product
     * through Bagisto's media repository.
     *
     * @param  string[]  $urls
     */
    public function attachToProduct(array $urls, Product $product): void
    {
        $files = $this->download($urls);

        if (empty($files)) {
            return;
        }

        $this->productImageRepository->upload(
            ['images' => ['files' => $files]],
            $product,
            'images'
        );
    }

    /**
     * Download the video URLs and attach them to the given product through
     * Bagisto's ProductVideoRepository, which stores non-image files as-is
     * under product/{id}/ (Requirement: import product videos).
     *
     * @param  string[]  $urls
     */
    public function attachVideosToProduct(array $urls, Product $product): void
    {
        $files = $this->download($urls);

        if (empty($files)) {
            return;
        }

        $this->productVideoRepository->upload(
            ['videos' => ['files' => $files]],
            $product,
            'videos'
        );
    }

    /**
     * Fetch a single URL and wrap it in a test-mode UploadedFile so it passes
     * isValid() without requiring an actual HTTP upload.
     *
     * @throws \RuntimeException when the response is not OK or the body is empty
     */
    protected function downloadOne(string $url): ?UploadedFile
    {
        $response = Http::get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Non-OK HTTP response: '.$response->status());
        }

        $body = $response->body();

        if ($body === '') {
            throw new \RuntimeException('Empty image body.');
        }

        $extension = $this->guessExtension($url);

        $tempPath = tempnam(sys_get_temp_dir(), 'aliexpress_img_');

        if ($tempPath === false) {
            throw new \RuntimeException('Unable to create temp file for image.');
        }

        // Give the temp file a sensible extension so mime detection and
        // Bagisto's image processing accept it.
        $targetPath = $tempPath.'.'.$extension;

        file_put_contents($targetPath, $body);

        @unlink($tempPath);

        return new UploadedFile(
            $targetPath,
            basename($targetPath),
            null,        // mime is guessed from the actual file contents
            null,
            true         // test mode: bypass is_uploaded_file() so isValid() passes
        );
    }

    /**
     * Derive a reasonable file extension from the URL path. Images default to
     * jpg; recognised video extensions are preserved (mp4/webm/mkv/mov) so
     * Bagisto stores the video file with a usable extension.
     */
    protected function guessExtension(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'mp4', 'webm', 'mkv', 'mov'];

        if ($extension !== '' && in_array($extension, $allowed, true)) {
            return $extension;
        }

        return 'jpg';
    }
}

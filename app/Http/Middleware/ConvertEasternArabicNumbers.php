<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertEasternArabicNumbers
{
    /**
     * Eastern Arabic numerals (Indic).
     */
    protected array $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    /**
     * Western Arabic numerals (Latin/English).
     */
    protected array $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);

            if (is_array($data)) {
                $response->setData($this->convertData($data));
            }
        } elseif (method_exists($response, 'getContent') && is_string($response->getContent())) {
            $content = $response->getContent();

            if ($content && $this->hasEasternArabicDigits($content)) {
                $response->setContent(str_replace($this->eastern, $this->western, $content));
            }
        }

        return $response;
    }

    /**
     * Check if string contains any Eastern Arabic digits.
     */
    protected function hasEasternArabicDigits(string $content): bool
    {
        return (bool) preg_match('/[٠-٩]/u', $content);
    }

    /**
     * Recursively convert array data values.
     */
    protected function convertData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                if ($this->hasEasternArabicDigits($value)) {
                    $data[$key] = str_replace($this->eastern, $this->western, $value);
                }
            } elseif (is_array($value)) {
                $data[$key] = $this->convertData($value);
            }
        }

        return $data;
    }
}

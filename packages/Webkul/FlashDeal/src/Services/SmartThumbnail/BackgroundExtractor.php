<?php

namespace Webkul\FlashDeal\Services\SmartThumbnail;

use Intervention\Image\Interfaces\ImageInterface;

class BackgroundExtractor
{
    /**
     * Sample size from each corner (in pixels from edge).
     */
    protected int $sampleDepth = 15;

    /**
     * Extract dominant background color from image corners.
     *
     * Returns an RGB array ['red' => int, 'green' => int, 'blue' => int].
     */
    public function extract(ImageInterface $image): array
    {
        $width = $image->width();
        $height = $image->height();

        if ($width <= 0 || $height <= 0) {
            return ['red' => 255, 'green' => 255, 'blue' => 255];
        }

        $samples = [];
        $depth = min($this->sampleDepth, intval($width / 4), intval($height / 4));
        $step = max(1, intval($depth / 5));

        // Sample from 4 corner regions
        $cornerRegions = [
            ['x_start' => 0, 'y_start' => 0],
            ['x_start' => max(0, $width - $depth), 'y_start' => 0],
            ['x_start' => 0, 'y_start' => max(0, $height - $depth)],
            ['x_start' => max(0, $width - $depth), 'y_start' => max(0, $height - $depth)],
        ];

        foreach ($cornerRegions as $region) {
            for ($y = $region['y_start']; $y < min($region['y_start'] + $depth, $height); $y += $step) {
                for ($x = $region['x_start']; $x < min($region['x_start'] + $depth, $width); $x += $step) {
                    $samples[] = $this->extractRgb($image->pickColor($x, $y));
                }
            }
        }

        if (empty($samples)) {
            return ['red' => 255, 'green' => 255, 'blue' => 255];
        }

        // Calculate median color (more robust than mean against outliers)
        $reds = array_column($samples, 'red');
        $greens = array_column($samples, 'green');
        $blues = array_column($samples, 'blue');

        sort($reds);
        sort($greens);
        sort($blues);

        $mid = intval(count($samples) / 2);

        return [
            'red' => $reds[$mid],
            'green' => $greens[$mid],
            'blue' => $blues[$mid],
        ];
    }

    /**
     * Get background color as hex string (e.g. '#f5f5f5').
     */
    public function extractHex(ImageInterface $image): string
    {
        $rgb = $this->extract($image);

        return sprintf('#%02x%02x%02x', $rgb['red'], $rgb['green'], $rgb['blue']);
    }

    /**
     * Check if background is predominantly light (white-ish / light gray).
     */
    public function isLightBackground(array $rgb): bool
    {
        $luminance = (0.299 * $rgb['red']) + (0.587 * $rgb['green']) + (0.114 * $rgb['blue']);

        return $luminance > 200;
    }

    /**
     * Safely extract RGB values from Intervention Image v3 Color object.
     */
    protected function extractRgb(mixed $color): array
    {
        if (is_object($color)) {
            if (method_exists($color, 'red') && method_exists($color, 'green') && method_exists($color, 'blue')) {
                return [
                    'red' => (int) (string) $color->red(),
                    'green' => (int) (string) $color->green(),
                    'blue' => (int) (string) $color->blue(),
                ];
            }

            if (method_exists($color, 'toRgb')) {
                $rgb = $color->toRgb();

                return [
                    'red' => (int) ($rgb->red ?? 255),
                    'green' => (int) ($rgb->green ?? 255),
                    'blue' => (int) ($rgb->blue ?? 255),
                ];
            }
        }

        return ['red' => 255, 'green' => 255, 'blue' => 255];
    }
}

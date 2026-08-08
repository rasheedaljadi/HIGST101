<?php

namespace Webkul\FlashDeal\Services\SmartThumbnail;

use Intervention\Image\Interfaces\ImageInterface;

class WhitespaceDetector
{
    protected int $colorThreshold = 28;

    /**
     * Detect subject bounding region within image.
     */
    public function detectBoundingRegion(ImageInterface $image): array
    {
        $width = $image->width();
        $height = $image->height();

        if ($width <= 0 || $height <= 0) {
            return [
                'x_min' => 0,
                'y_min' => 0,
                'x_max' => $width,
                'y_max' => $height,
                'width' => $width,
                'height' => $height,
            ];
        }

        // Sampling background color from 4 corners
        $corners = [
            $this->extractRgb($image->pickColor(0, 0)),
            $this->extractRgb($image->pickColor(max(0, $width - 1), 0)),
            $this->extractRgb($image->pickColor(0, max(0, $height - 1))),
            $this->extractRgb($image->pickColor(max(0, $width - 1), max(0, $height - 1))),
        ];

        $avgBgR = array_sum(array_column($corners, 'red')) / 4;
        $avgBgG = array_sum(array_column($corners, 'green')) / 4;
        $avgBgB = array_sum(array_column($corners, 'blue')) / 4;

        $stepX = max(1, (int) floor($width / 80));
        $stepY = max(1, (int) floor($height / 80));

        $minX = $width;
        $minY = $height;
        $maxX = 0;
        $maxY = 0;
        $subjectPixelCount = 0;

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                $rgb = $this->extractRgb($image->pickColor($x, $y));

                $deltaC = sqrt(
                    pow($rgb['red'] - $avgBgR, 2) +
                    pow($rgb['green'] - $avgBgG, 2) +
                    pow($rgb['blue'] - $avgBgB, 2)
                );

                if ($deltaC > $this->colorThreshold) {
                    $subjectPixelCount++;
                    if ($x < $minX) {
                        $minX = $x;
                    }
                    if ($y < $minY) {
                        $minY = $y;
                    }
                    if ($x > $maxX) {
                        $maxX = $x;
                    }
                    if ($y > $maxY) {
                        $maxY = $y;
                    }
                }
            }
        }

        // Fallback to center 80% box if no subject pixels detected
        if ($subjectPixelCount < 10 || $minX >= $maxX || $minY >= $maxY) {
            $minX = (int) ($width * 0.05);
            $minY = (int) ($height * 0.05);
            $maxX = (int) ($width * 0.95);
            $maxY = (int) ($height * 0.95);
        }

        $subjWidth = max(1, $maxX - $minX);
        $subjHeight = max(1, $maxY - $minY);

        return [
            'x_min' => $minX,
            'y_min' => $minY,
            'x_max' => $maxX,
            'y_max' => $maxY,
            'width' => $subjWidth,
            'height' => $subjHeight,
        ];
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

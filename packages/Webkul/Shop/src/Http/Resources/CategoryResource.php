<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'position' => $this->position,
            'display_mode' => $this->display_mode,
            'description' => $this->description,
            'logo' => $this->when($this->logo_path, fn () => [
                'small_image_url' => $this->resolveImageUrl('small', $this->logo_path, 'logo'),
                'medium_image_url' => $this->resolveImageUrl('medium', $this->logo_path, 'logo'),
                'large_image_url' => $this->resolveImageUrl('large', $this->logo_path, 'logo'),
                'original_image_url' => $this->resolveImageUrl('original', $this->logo_path, 'logo'),
            ]),
            'banner' => $this->when($this->banner_path, fn () => [
                'small_image_url' => $this->resolveImageUrl('small', $this->banner_path, 'banner'),
                'medium_image_url' => $this->resolveImageUrl('medium', $this->banner_path, 'banner'),
                'large_image_url' => $this->resolveImageUrl('large', $this->banner_path, 'banner'),
                'original_image_url' => $this->resolveImageUrl('original', $this->banner_path, 'banner'),
            ]),
            'meta' => [
                'title' => $this->meta_title,
                'keywords' => $this->meta_keywords,
                'description' => $this->meta_description,
            ],
            'translations' => $this->translations,
            'additional' => $this->additional,
        ];
    }

    /**
     * Resolve cached image URL or generate cached file on demand, falling back to Storage URL.
     */
    protected function resolveImageUrl(string $template, string $path, string $type = 'logo'): string
    {
        $cachedPath = public_path('cache/'.$template.'/'.$path);

        if (file_exists($cachedPath)) {
            return url('cache/'.$template.'/'.$path).'?v='.filemtime($cachedPath);
        }

        $originalPath = storage_path('app/public/'.$path);

        if (file_exists($originalPath)) {
            try {
                $targetDir = dirname($cachedPath);

                if (! file_exists($targetDir)) {
                    @mkdir($targetDir, 0777, true);
                }

                if ($template === 'original') {
                    @copy($originalPath, $cachedPath);
                } elseif ($type === 'logo') {
                    $sizes = [
                        'small' => [110, 110],
                        'medium' => [200, 200],
                        'large' => [400, 400],
                    ];

                    $size = $sizes[$template] ?? [200, 200];
                    $image = image_manager()->read($originalPath);
                    $image = $image->scaleDown($size[0], $size[1]);
                    @file_put_contents($cachedPath, (string) $image->encodeByMediaType());
                } else {
                    $templates = config('imagecache.templates', []);
                    $templateClass = $templates[$template] ?? null;

                    if ($templateClass && class_exists($templateClass)) {
                        $filter = new $templateClass;

                        if (method_exists($filter, 'applyFilter')) {
                            $image = image_manager()->read($originalPath);
                            $image = $filter->applyFilter($image);
                            @file_put_contents($cachedPath, (string) $image->encodeByMediaType());
                        } else {
                            @copy($originalPath, $cachedPath);
                        }
                    } else {
                        @copy($originalPath, $cachedPath);
                    }
                }

                if (file_exists($cachedPath)) {
                    return url('cache/'.$template.'/'.$path).'?v='.filemtime($cachedPath);
                }
            } catch (\Throwable) {
                // Ignore and fall through to Storage::url
            }
        }

        return Storage::url($path);
    }
}

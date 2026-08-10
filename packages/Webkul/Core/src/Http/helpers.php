<?php

use Stevebauman\Purify\Facades\Purify;
use Webkul\Core\Facades\Acl;
use Webkul\Core\Facades\Core;
use Webkul\Core\Facades\Menu;
use Webkul\Core\Facades\SystemConfig;

if (! function_exists('core')) {
    /**
     * Core helper.
     *
     * @return Webkul\Core\Core
     */
    function core()
    {
        return Core::getFacadeRoot();
    }
}

if (! function_exists('menu')) {
    /**
     * Menu helper.
     *
     * @return Webkul\Core\Menu
     */
    function menu()
    {
        return Menu::getFacadeRoot();
    }
}

if (! function_exists('acl')) {
    /**
     * Acl helper.
     *
     * @return Webkul\Core\Acl
     */
    function acl()
    {
        return Acl::getFacadeRoot();
    }
}

if (! function_exists('system_config')) {
    /**
     * System Config helper.
     *
     * @return Webkul\Core\SystemConfig
     */
    function system_config()
    {
        return SystemConfig::getFacadeRoot();
    }
}

if (! function_exists('clean_path')) {
    /**
     * Clean path.
     */
    function clean_path(string $path): string
    {
        return collect(explode('/', $path))
            ->filter(fn ($segment) => ! empty($segment))
            ->join('/');
    }
}

if (! function_exists('clean_content')) {
    /**
     * Clean content.
     */
    function clean_content(string $content): string
    {
        $cleaned = Purify::clean($content);

        $patterns = [
            '/\{\{.*?\}\}/',
            '/\{!!.*?!!\}/',
            '/@(php|if|else|endif|foreach|endforeach|for|endfor|while|endwhile|switch|endswitch|case|break|continue|include|extends|section|endsection|yield|push|endpush|stack|endstack)/',
            '/<\?php.*?\?>/s',
        ];

        foreach ($patterns as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned);
        }

        $cleaned = str_replace(
            ['{{', '}}', '{!!', '!!}'],
            ['&#123;&#123;', '&#125;&#125;', '&#123;!!', '!!&#125;'],
            $cleaned
        );

        return $cleaned;
    }
}

if (! function_exists('array_permutation')) {
    function array_permutation($input)
    {
        $results = [];

        foreach ($input as $key => $values) {
            if (empty($values)) {
                continue;
            }

            if (empty($results)) {
                foreach ($values as $value) {
                    $results[] = [$key => $value];
                }
            } else {
                $append = [];

                foreach ($results as &$result) {
                    $result[$key] = array_shift($values);

                    $copy = $result;

                    foreach ($values as $item) {
                        $copy[$key] = $item;
                        $append[] = $copy;
                    }

                    array_unshift($values, $result[$key]);
                }

                $results = array_merge($results, $append);
            }
        }

        return $results;
    }
}

if (! function_exists('clean_product_description')) {
    /**
     * Clean product description from imported metadata noise like modname=..., cols=..., colspace=...
     */
    function clean_product_description(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        $patterns = [
            '/modname\s*=\s*.*?(?=<|\n|\r|$)/iu',
            '/cols\s*=\s*\d+.*?(?=<|\n|\r|$)/iu',
            '/colspace\s*=\s*\d+.*?(?=<|\n|\r|$)/iu',
            '/rowspace\s*=\s*\d+.*?(?=<|\n|\r|$)/iu',
            '/align\s*=\s*(center|centre|مركز)(?=<|\n|\r|$)/iu',
        ];

        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }

        $content = preg_replace('/<(div|p|span)[^>]*>\s*<\/\1>/iu', '', $content);

        return trim($content);
    }
}

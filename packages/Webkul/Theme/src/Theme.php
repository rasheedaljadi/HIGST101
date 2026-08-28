<?php

namespace Webkul\Theme;

use Illuminate\Support\Facades\Vite;

class Theme
{
    /**
     * Contains theme parent.
     *
     * @var Theme
     */
    public $parent;

    /**
     * Create a new theme instance.
     *
     * @param  string  $code
     * @param  string  $name
     * @param  string  $assetsPath
     * @param  string  $viewsPath
     * @return void
     */
    public function __construct(
        public $code,
        public $name = null,
        public $assetsPath = null,
        public $viewsPath = null,
        public $viewsNamespace = null,
        public $vite = []
    ) {
        $this->assetsPath = $assetsPath === null ? $code : $assetsPath;

        $this->viewsPath = $viewsPath === null ? $code : $viewsPath;
    }

    /**
     * Sets the parent.
     *
     * @param  Theme
     * @return void
     */
    public function setParent(Theme $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Return the parent.
     *
     * @return Theme
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Return all the possible view paths.
     *
     * @return array
     */
    public function getViewPaths()
    {
        $paths = [];

        $theme = $this;

        do {
            if (substr($theme->viewsPath, 0, 1) === DIRECTORY_SEPARATOR) {
                $path = base_path(substr($theme->viewsPath, 1));
            } else {
                $path = $theme->viewsPath;
            }

            if (! in_array($path, $paths)) {
                $paths[] = $path;
            }
        } while ($theme = $theme->parent);

        return $paths;
    }

    /**
     * Convert to asset url based on current theme.
     *
     * @return string
     */
    public function url(string $url)
    {
        try {
            $viteUrl = trim($this->vite['package_assets_directory'] ?? 'src/Resources/assets', '/').'/'.$url;

            return Vite::useHotFile($this->vite['hot_file'] ?? 'admin-default-vite.hot')
                ->useBuildDirectory($this->vite['build_directory'] ?? 'themes/admin/default/build')
                ->asset($viteUrl);
        } catch (\Exception $e) {
            $buildDir = trim($this->vite['build_directory'] ?? 'themes/admin/default/build', '/');

            return asset($buildDir.'/'.$url);
        }
    }

    /**
     * Set bagisto vite.
     *
     * @return \Illuminate\Foundation\Vite
     */
    public function setBagistoVite(array $entryPoints)
    {
        $hotFile = $this->vite['hot_file'] ?? (request()->is('admin*') ? 'admin-default-vite.hot' : 'shop-default-vite.hot');
        $buildDir = $this->vite['build_directory'] ?? (request()->is('admin*') ? 'themes/admin/default/build' : 'themes/shop/default/build');

        return Vite::useHotFile($hotFile)
            ->useBuildDirectory($buildDir)
            ->withEntryPoints($entryPoints);
    }
}

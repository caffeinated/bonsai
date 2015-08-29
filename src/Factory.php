<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Caffeinated\Bonsai;

use Caffeinated\Bonsai\Contracts\Factory as FactoryContract;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Routing\UrlGenerator;

/**
 * This is the Bonsai.
 *
 * @package        Caffeinated\Bonsai
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class Factory implements FactoryContract
{

    /**
     * @var string
     */
    protected $cachePath;

    /** @var string */
    protected $assetClass;

    /** @var string */
    protected $assetGroupClass;

    /**
     * @var AssetGroup[]
     */
    protected $assetGroups = [ ];

    protected $globalFilters = [ ];

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Contracts\Routing\UrlGenerator
     */
    protected $url;

    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * @var \Collective\Html\HtmlBuilder
     */
    protected $html;

    /**
     * @var bool
     */
    protected $hasThemes;

    /**
     * @var \Caffeinated\Themes\ThemeFactory
     */
    protected $themes;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /** Instantiates the class
     *
     * @param \Illuminate\Contracts\Container\Container   $container
     * @param \Illuminate\Contracts\Filesystem\Filesystem $files
     * @param \Illuminate\Contracts\Routing\UrlGenerator  $url
     * @param \Illuminate\Contracts\Config\Repository     $config
     * @internal param \Caffeinated\Themes\Contracts\ThemeFactory|\Laradic\Themes\Contracts\ThemeFactory $themes
     */
    public function __construct(Container $container, Filesystem $files, UrlGenerator $url, Config $config)
    {
        $this->container       = $container;
        $this->files           = $files;
        $this->url             = $url;
        $this->config          = $config;
        $this->assetClass      = $this->config->get('caffeinated.bonsai.asset_class');
        $this->assetGroupClass = $this->config->get('caffeinated.bonsai.asset_group_class');

        $this->setCachePath(public_path($this->config->get('caffeinated.bonsai.cache_path')));

        $this->html = $container->make('html');

        if ($this->hasThemes = $container->bound('caffeinated.themes')) {
            $this->themes = $container->make('caffeinated.themes');
        }
    }

    /**
     * Create a single Asset
     *
     * @param string $handle       The ID/key for this asset
     * @param string $path         File location path
     * @param array  $dependencies Optional dependencies
     * @return Asset
     */
    public function make($handle, $path, array $dependencies = [ ])
    {
        /**
         * @var Asset $asset
         */
        $asset   = new $this->assetClass($handle, $this->resolvePath($path), $dependencies);
        $filters = $this->getGlobalFilters($asset->getExt());
        foreach ($filters as $filter) {
            $asset->ensureFilter($filter);
        }

        return $asset;
    }

    /**
     * resolvePath
     *
     * @param $path
     * @return string
     */
    public function resolvePath($path)
    {
        if ($this->hasThemes) {
            return $this->themes->assetPath($path);
        }

        return $path;
    }

    /**
     * url
     *
     * @param string $assetPath
     * @return string
     */
    public function url($assetPath = '')
    {
        return $this->url->to($this->resolvePath($assetPath));
    }

    /**
     * uri
     *
     * @param string $assetPath
     * @return string
     */
    public function uri($assetPath = '')
    {
        return $this->url->to($this->resolvePath($assetPath));
    }

    /**
     * script
     *
     * @param string $assetPath
     * @param array  $attr
     * @param bool   $secure
     * @return string
     */
    public function script($assetPath = '', array $attr = [ ], $secure = false)
    {
        return app('html')->script($this->url($assetPath), $attr, $secure);
    }

    /**
     * style
     *
     * @param string $assetPath
     * @param array  $attr
     * @param bool   $secure
     * @return string
     */
    public function style($assetPath = '', array $attr = [ ], $secure = false)
    {
        return app('html')->style($this->url($assetPath), $attr, $secure);
    }

    /**
     * addGlobalFilter
     *
     * @param $extension
     * @param $callback
     * @return $this
     */
    public function addGlobalFilter($extension, $callback)
    {
        if (is_string($callback)) {
            $callback = function () use ($callback) {
            
                return new $callback;
            };
        } elseif (! $callback instanceof \Closure) {
            throw new \InvalidArgumentException('Callback is not a closure or reference string.');
        }
        $this->globalFilters[ $extension ][] = $callback;

        return $this;
    }

    /**
     * getGlobalFilters
     *
     * @param $extension
     * @return array
     */
    public function getGlobalFilters($extension)
    {
        $filters = array();
        if (! isset($this->globalFilters[ $extension ])) {
            return array();
        }
        foreach ($this->globalFilters[ $extension ] as $cb) {
            $filters[] = $cb();
        }

        return $filters;
    }

    /**
     * group
     *
     * @param          $name
     * @param callable $cb
     * @return AssetGroup
     */
    public function group($name)
    {
        if (isset($this->assetGroups[ $name ])) {
            return $this->assetGroups[ $name ];
        } else {
            $this->assetGroups[ $name ] = new $this->assetGroupClass($this, $name);

            return $this->assetGroups[ $name ];
        }
    }




    //
    /* GETTERS & SETTERS */
    //
    /**
     * getThemes
     *
     * @return \Caffeinated\Themes\ThemeFactory
     */
    public function getThemes()
    {
        return $this->themes;
    }

    public function getAssetClass()
    {
        return $this->assetClass;
    }

    /**
     * get cacheDir value
     *
     * @return mixed
     */
    public function getCachePath()
    {
        return $this->cachePath;
    }

    public function deleteAllCached()
    {
        $this->files->delete($this->files->files($this->getCachePath()));
    }

    /**
     * Set the cachePath value
     *
     * @param string $cachePath
     * @return Factory
     */
    public function setCachePath($cachePath)
    {
        if (! $this->files->exists($cachePath)) {
            $this->files->makeDirectory($cachePath);
        }
        $this->cachePath = $cachePath;

        return $this;
    }

    /**
     * Set the assetClass value
     *
     * @param string $assetClass
     * @return Factory
     */
    public function setAssetClass($assetClass)
    {
        $this->assetClass = $assetClass;

        return $this;
    }

    /**
     * Set the assetGroupClass value
     *
     * @param string $assetGroupClass
     * @return Factory
     */
    public function setAssetGroupClass($assetGroupClass)
    {
        $this->assetGroupClass = $assetGroupClass;

        return $this;
    }

    /**
     * get container value
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * get html value
     *
     * @return \Collective\Html\HtmlBuilder
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * is hasThemes value
     *
     * @return boolean
     */
    public function isHasThemes()
    {
        return $this->hasThemes;
    }
}

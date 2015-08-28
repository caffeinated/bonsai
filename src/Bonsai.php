<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Caffeinated\Bonsai;

use Caffeinated\Beverage\Str;
use Caffeinated\Bonsai\Contracts\Bonsai as BonsaiContract;
use Caffeinated\Themes\Contracts\ThemeFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\NamespacedItemResolver;

/**
 * This is the Bonsai.
 *
 * @package        Caffeinated\Bonsai
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class Bonsai implements BonsaiContract
{
    /**
     * @var \Laradic\Themes\ThemeFactory
     */
    protected $themes;

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

    protected $files;

    protected $url;

    /** Instantiates the class
     *
     * @param \Laradic\Themes\Contracts\ThemeFactory $themes
     */
    public function __construct(ThemeFactory $themes, Filesystem $files, UrlGenerator $url)
    {
        $this->themes = $themes;
        $this->files = $files;
        $this->url = $url;
    }

    /**
     * Create a single Asset
     *
     * @param string $handle       The ID/key for this asset
     * @param string $path         File location path
     * @param array  $dependencies Optional dependencies
     * @return \Laradic\Themes\Assets\Asset
     */
    public function make($handle, $path, array $dependencies = [ ])
    {
        $asset   = new $this->assetClass($handle, $this->getPath($path), $dependencies);
        $filters = $this->getGlobalFilters($asset->getExt());
        foreach ( $filters as $filter )
        {
            $asset->ensureFilter($filter);
        }

        return $asset;
    }

    /**
     * url
     *
     * @param string $assetPath
     * @return string
     */
    public function url($assetPath = '')
    {
        return $this->toUrl($this->getPath($assetPath));
    }

    /**
     * uri
     *
     * @param string $assetPath
     * @return string
     */
    public function uri($assetPath = '')
    {
        return $this->relativePath($this->getPath($assetPath));
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

    public function addGlobalFilter($extension, $callback)
    {
        if ( is_string($callback) )
        {
            $callback = function () use ($callback)
            {
                return new $callback;
            };
        }
        elseif ( ! $callback instanceof \Closure )
        {
            throw new \InvalidArgumentException('Callback is not a closure or reference string.');
        }
        $this->globalFilters[ $extension ][] = $callback;

        return $this;
    }

    public function getGlobalFilters($extension)
    {
        $filters = array();
        if ( ! isset($this->globalFilters[ $extension ]) )
        {
            return array();
        }
        foreach ( $this->globalFilters[ $extension ] as $cb )
        {
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
        if ( isset($this->assetGroups[ $name ]) )
        {
            return $this->assetGroups[ $name ];
        }
        else
        {
            $this->assetGroups[ $name ] = new $this->assetGroupClass($this, $name);

            return $this->assetGroups[ $name ];
        }
    }

    /**
     * getPath
     *
     * @param null $key
     * @return string
     */
    public function getPath($key = null)
    {
        list($section, $relativePath, $extension) = with(new NamespacedItemResolver)->parseKey($key);
        if ( $key === null )
        {
            return $this->toUrl($this->themes->getActive()->getPath('assets'));
        }
        if ( $relativePath === null or strlen($relativePath) === 0 )
        {
            if ( array_key_exists($section, View::getFinder()->getHints()) )
            {
                return $this->toUrl($this->themes->getActive()->getCascadedPath('namespaces', $section, 'assets'));
            }

            return $this->toUrl($this->themes->getActive()->getCascadedPath('packages', $section, 'assets'));
        }
        if ( isset($section) )
        {
            if ( array_key_exists($section, View::getFinder()->getHints()) )
            {
                $paths = $this->themes->getCascadedPaths('namespaces', $section, 'assets');
            }
            else
            {
                $paths = $this->themes->getCascadedPaths('packages', $section, 'assets');
            }
        }
        else
        {
            $paths = $this->themes->getCascadedPaths(null, null, 'assets');
        }
        foreach ( $paths as $path )
        {
            $file = rtrim($path, '/') . '/' . $relativePath . '.' . $extension;
            if ( File::exists($file) )
            {
                return $file;
            }
        }

        return $file;
    }

    /**
     * relativePath
     *
     * @param $path
     * @return string
     */
    protected function relativePath($path)
    {
        $path = Str::create($path)->removeLeft(public_path());
        if ( $path->endsWith('.') )
        {
            $path = $path->removeRight('.');
        }

        return (string)$path;
    }

    /**
     * toUrl
     *
     * @param $path
     * @return string
     */
    protected function toUrl($path)
    {
        if ( Str::startsWith($path, public_path(), true) )
        {
            $path = $this->relativePath($path);
        }

        return $this->url->to($path);
    }



    //
    /* GETTERS & SETTERS */
    //
    /**
     * getThemes
     *
     * @return \Laradic\Themes\Contracts\ThemeFactory|\Laradic\Themes\ThemeFactory
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
     * @return AssetFactory
     */
    public function setCachePath($cachePath)
    {
        $this->cachePath = $cachePath;

        return $this;
    }

    /**
     * Set the assetClass value
     *
     * @param string $assetClass
     * @return AssetFactory
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
     * @return AssetFactory
     */
    public function setAssetGroupClass($assetGroupClass)
    {
        $this->assetGroupClass = $assetGroupClass;

        return $this;
    }
}

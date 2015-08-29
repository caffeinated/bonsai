<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Caffeinated\Bonsai;

use Assetic\Filter\HashableInterface;
use Caffeinated\Beverage\Sorter;
use Caffeinated\Beverage\Str;
use Caffeinated\Bonsai\Contracts\Factory as Factory;
use Illuminate\Contracts\Cache\Factory as Cache;
use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;

/**
 * This is the AssetGroup.
 *
 * @package        Caffeinated\Bonsai
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class AssetGroup
{
    /**
     * The name of this asset group
     *
     * @var string
     */
    protected $name;

    /**
     * @var \Caffeinated\Bonsai\Factory
     */
    protected $factory;

    /**
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cache;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $files;

    protected $scripts = [ ];

    protected $styles = [ ];

    protected $sorter;

    protected $filters = [ ];

    /**
     * @param \Caffeinated\Bonsai\Factory|\Caffeinated\Bonsai\Contracts\Factory $factory
     * @param \Illuminate\Contracts\Cache\Factory                               $cache
     * @param \Illuminate\Contracts\Filesystem\Filesystem                       $files
     * @param                                                                 $name
     */
    public function __construct(Factory $factory, Cache $cache, Filesystem $files, $name)
    {
        $this->name       = $name;
        $this->factory    = $factory;
        $this->cache      = $cache;
        $this->files      = $files;
        $this->collection = new AssetCollection();
    }


    public function add($handle, $path = null, array $dependencies = [ ])
    {
        if ($handle instanceof Asset) {
            $asset  = $handle;
            $handle = $asset->getHandle();
        } elseif (! is_null($path)) {
            $asset = $this->factory->make($handle, $path);
        } else {
            throw new \InvalidArgumentException("Parameter path was null: $path");
        }
        $type = $this->resolveType($asset->getExt());
        if (count($dependencies) > 0 and false === true) {
            $_deps = [ ];
            foreach ($dependencies as $dep) {
                if (isset($this->{"{$type}s"}[ $dep ])) {
                    $_deps [] = $this->{"{$type}s"}[ $dep ][ 'asset' ];
                }
            }
            $asset->setDependencies($dependencies);
        }
        $asset->setDependencies($dependencies);
        $this->{"{$type}s"}[ $handle ] = [
            'handle'  => $handle,
            'asset'   => $asset,
            'type'    => $type,
            'depends' => $dependencies
        ];

        #$this->collection->add($asset);
        return $this;
    }

    protected function resolveType($ext)
    {
        $style  = [ 'css', 'scss', 'sass', 'less' ];
        $script = [ 'js', 'ts', 'cs' ];
        if (in_array($ext, $style, true)) {
            return 'style';
        }
        if (in_array($ext, $script, true)) {
            return 'script';
        }

        return 'other';
    }

    public function addFilter($extension, $callback)
    {
        if (is_string($callback)) {
            $callback = function () use ($callback) {
            
                return new $callback;
            };
        } elseif (! $callback instanceof \Closure) {
            throw new InvalidArgumentException('Callback is not a closure or reference string.');
        }
        $this->filters[ $extension ][] = $callback;

        return $this;
    }

    public function getFilters($extension)
    {
        $filters = array();
        if (! isset($this->filters[ $extension ])) {
            return array();
        }
        foreach ($this->filters[ $extension ] as $cb) {
            $filters[] = new $cb();
        }

        return $filters;
    }

    public function render($type, $combine = true)
    {
        $assets           = $this->getSorted($type);
        $assets           = $combine ? new AssetCollection($assets) : $assets;
        $lastModifiedHash = '';
        foreach (($combine ? $assets->all() : $assets) as $asset) {
            if (! $asset instanceof Asset) {
                continue;
            }
            foreach ($this->getFilters($asset->getExt()) as $filter) {
                $asset->ensureFilter($filter);
            }
        }
        if ($combine) {
            $assets = array( $assets );
        }
        $urls         = [ ];
        $cachePath    = $this->factory->getCachePath();
        $cachedAssets = \File::files($this->factory->getCachePath());
        $theme        = $this->factory->getThemes()->getActive();
        $renderExt    = $type === 'styles' ? 'css' : 'js';
        foreach ($assets as $asset) {
            $renewCachedFile  = false;
            $lastModifiedHash = md5($asset->getLastModified());
            $cacheKey         = $asset->getCacheKey();
            if (Cache::has($cacheKey) and Cache::get($cacheKey) !== $lastModifiedHash) {
                $renewCachedFile = true;
            }
            Cache::forever($cacheKey, $lastModifiedHash);
            $filename = Str::replace($theme->getSlug(), '/', '.') . '.' . $asset->getHandle() . '.' . md5($asset->getCacheKey()) . '.' . $renderExt;
            $path     = $cachePath . '/' . $filename;
            if ($renewCachedFile) {
                File::delete($path);
            }
            if (! File::exists($path)) {
                File::put($path, $asset->dump());
            }
            $urls[] = Str::removeLeft($path, public_path());
        }
        $htmlTags = '';
        foreach ($urls as $url) {
            $htmlTags .= $type === 'scripts' ? app('html')->script($url) : app('html')->style($url);
        }

        return $htmlTags;
    }

    public function get($type, $handle)
    {
        return $this->{"{$type}s"}[ $handle ];
    }

    public function has($type, $handle)
    {
        return isset($this->{"{$type}s"}[$handle]);
    }

    /**
     * getSorted
     *
     * @param string $type 'scripts' or 'styles'
     * @return Asset[]
     */
    public function getSorted($type)
    {
        $sorter = new Sorter();
        foreach ($this->{"{$type}"} as $handle => $assetData) {
            $sorter->addItem($assetData[ 'asset' ]);
        }
        $assets = [ ];
        foreach ($sorter->sort() as $handle) {
            $assets[] = $this->get(Str::singular($type), $handle)[ 'asset' ];
        }

        return $assets;
    }

    /**
     * getAssets
     *
     * @param string $type 'scripts' or 'styles'
     * @return mixed
     */
    public function getAssets($type)
    {
        return $this->{"{$type}"};
    }

    /**
     * Get the value of name
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getCacheKey($type)
    {
        $key = md5($this->name . $type . $this->factory->getThemes()->getActive()->getSlug());
        foreach ($this->filters as $filter) {
            $key .= $filter instanceof HashableInterface ? $filter->hash() : serialize($filter);
        }

        return md5($key);
    }
}

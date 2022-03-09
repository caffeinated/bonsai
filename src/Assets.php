<?php
namespace Caffeinated\Bonsai;

use Caffeinated\Bonsai\Dependencies;
use Illuminate\Support\Collection;

class Assets
{

    /**
     * @var Illuminate\Support\Collection
     */
    protected $collection;

    /**
     * @var string
     */
    protected $lastAddedAsset = '';

    /**
     * @var string
     */
    protected $lastAddedType = '';

    /**
     * Constructor method.
     *
     * @return null
     */
    public function __construct()
    {
        $this->collection = new Collection(['css' => array(), 'js' => array()]);
    }

    /**
     * Add a dependency to an asset.
     *
     * @return Asset
     */
    public function dependsOn($dependency)
    {
        if (! $dependency || ! $this->lastAddedAsset) {
            return $this;
        }

        $collection = $this->collection->get($this->lastAddedType);

        foreach ($collection as $path => $item) {
            if ($path === $this->lastAddedAsset) {
                $collection[$path] = array(
                    'namespace'  => $item['namespace'],
                    'dependency' => $dependency
                );

                $this->collection->put($this->lastAddedType, $collection);
            }
        }

        return $this;
    }

    /**
     * Builds the CSS HTML tags.
     *
     * @return Closure|string
     */
    public function css()
    {
        $cssCollection = $this->sortDependencies('css');
        $output        = '';

        foreach ($cssCollection as $key => $value) {
            $output .= '<link rel="stylesheet" href="'.$value.'">'."\n";
        }

        return $output;
    }

    /**
     * Builds the CSS HTML tags.
     *
     * @return Closure|string
     */
    public function js()
    {
        $jsCollection = $this->sortDependencies('js');
        $output       = '';

        foreach ($jsCollection as $key => $value) {
            $output .= '<script type="text/javascript" src="'.$value.'"></script>'."\n";
        }

        return $output;
    }

    /**
     * Determines if the passed asset is indeed an asset.
     *
     * @param  string  $asset
     * @return bool
     */
    protected function isAsset($asset)
    {
        return $this->isJs($asset) || $this->isCss($asset);
    }

    /**
     * Determines if the passed asset is a Javascript file.
     *
     * @param  string  $asset
     * @return bool
     */
    protected function isJs($asset)
    {
        return is_string($asset) && stripos($asset, '.js') !== false;
    }

    /**
     * Determines if the passed asset is a CSS file.
     *
     * @param  string  $asset
     * @return bool
     */
    protected function isCss($asset)
    {
        return is_string($asset) && stripos($asset, '.css') !== false;
    }

    /**
     * Determines if the passed asset is a Bonsai JSON file.
     *
     * @param  string  $asset
     * @return bool
     */
    protected function isBonsai($asset)
    {
        return is_array($asset) || (is_string($asset) && preg_match('/bonsai\.json$/i', $asset));
    }

    /**
     * Add an asset file to the collection.
     *
     * @param  array|string  $assets
     * @return Assets
     */
    public function add($assets, $namespace = null)
    {
        if ($this->isBonsai($assets)) {
            return $this->parseBonsai($assets);
        }

        if (! $this->isAsset($assets)) {
            $this->lastAddedAsset = '';

            return $this;
        }

        $type       = $this->isCss($assets) ? 'css' : 'js';
        $collection = $this->collection->get($type);

        if (! in_array($assets, $collection)) {
            $collection[$assets] = array(
                'namespace'  => $namespace
            );

            $this->collection->put($type, $collection);

            $this->lastAddedType  = $type;
            $this->lastAddedAsset = $assets;
        }

        return $this;
    }

    /**
     * Parse a bonsai.json file and add the assets to the collection.
     *
     * @param  string|array  $assets
     * @return Assets
     */
    protected function parseBonsai($assets)
    {
        if (is_string($assets)) {
            $file   = file_get_contents($assets);
            $assets = json_decode($file, true) ?: [];
        }

        foreach ($assets as $path => $meta) {
            $path = is_numeric($path) ? $meta : $path;
            $meta = is_array($meta) ? $meta : [];

            $this->add($path, $meta['namespace'] ?? null)
                ->dependsOn($meta['dependency'] ?? null);
        }

        return $this;
    }

    /**
     * Sorts the dependencies of all assets, to ensure dependant
     * assets are loaded first.
     *
     * @param  string  $type
     * @return array
     */
    protected function sortDependencies($type)
    {
        $assets = $this->collection->get($type);
        $dependencyList = array();

        foreach ($assets as $key => $value) {
            $dependencyList[$key] = [
                isset($value['dependency']) ? $this->getNamespacedAsset($value['dependency'], $type) : null
            ];
        }

        $dependencies = new Dependencies($dependencyList, true);

        $sortedDependencies = $dependencies->sort();

        return array_filter($sortedDependencies);
    }

    /**
     * Checks if the array has any circular references within
     * itself. This can be used to prevent any infinite loops
     * from sprouting up unknowingly.
     *
     * @param  array  $array
     * @return bool
     */
    protected function hasCircularReferences($array)
    {
        foreach ($array as $key => $value) {
            if (isset($array[$value]) and ($array[$value] == $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrievs the asset based on the defined namespace.
     *
     * @param  string  $namespace
     * @param  string  $type       (css|js)
     * @return string
     */
    protected function getNamespacedAsset($namespace, $type)
    {
        $collection = $this->collection->get($type);

        foreach ($collection as $key => $value) {
            if (($value['namespace'] ?? null) === $namespace) {
                return $key;
            }
        }
    }
}

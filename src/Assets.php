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
        $this->collection = new Collection(['css' => [], 'js' => []]);
    }

    /**
     * Add a new asset to the Bonsai collection.
     *
     * @return Asset
     */
    public function add($assets, $namespace = null)
    {
        if (is_array($assets)) {
            foreach ($assets as $asset) {
                $this->add($asset, $namespace);
            }
        } elseif ($this->isBonsai($assets)) {
            $this->parseBonsai($assets);
        } elseif ($this->isAsset($assets)) {
            $this->addAsset($assets, $namespace);
        }

        return $this;
    }

    /**
     * Add a dependency to an asset.
     *
     * @return Asset
     */
    public function dependsOn($dependency)
    {
        $collection = $this->collection->get($this->lastAddedType);

        foreach ($collection as $path => $item) {
            if (self::cleanAsset($path) === $this->lastAddedAsset) {
                $collection[$path] = array_merge($item, [
                    'dependency' => $dependency
                ]);

                $this->collection->put($this->lastAddedType, $collection);
                break;
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
        $cssCollection = $this->sortDependencies($this->collection->get('css'), 'css');
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
        $jsCollection = $this->sortDependencies($this->collection->get('js'), 'js');
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
        return stripos($asset, '.js') !== false;
    }

    /**
     * Determines if the passed asset is a CSS file.
     *
     * @param  string  $asset
     * @return bool
     */
    protected function isCss($asset)
    {
        return stripos($asset, '.css') !== false;
    }

    /**
     * Determines if the passed asset is a Bonsai JSON file.
     *
     * @param  string  $asset
     * @return bool
     */
    protected function isBonsai($asset)
    {
        return preg_match('/bonsai\.json$/i', $asset);
    }

    /**
     * Add an asset file to the collection.
     *
     * @param  array|string  $assets
     * @return Assets
     */
    protected function addAsset($assets, $namespace = null)
    {
        if (is_array($assets)) {
            foreach ($assets as $asset => $meta) {
                $this->addAsset($asset);
            }

            return $this;
        }

        $type       = ($this->isCss($assets)) ? 'css' : 'js';
        $collection = $this->collection->get($type);
        $cleanAsset = self::cleanAsset($assets);
        $existingAssets = array_map(function ($asset) {
            return self::cleanAsset($asset);
        }, array_keys($collection));

        if (! in_array($cleanAsset, $existingAssets)) {
            $collection[$assets] = array(
                'namespace'  => $namespace,
                'dependency' => array()
            );

            $this->collection->put($type, $collection);
        }

        $this->lastAddedType  = $type;
        $this->lastAddedAsset = $cleanAsset;

        return $this;
    }

    /**
     * Remove `?` anf `#` parts from asset
     *
     * @param  string  $asset
     * @return string
     */
    private static function cleanAsset($asset)
    {
        $asset = (string) $asset;

        $result = strstr($asset, '?', true);
        $asset = $result === false ? $asset : $result;

        $result = strstr($asset, '#', true);
        $asset = $result === false ? $asset : $result;

        return $asset;
    }

    /**
     * Parse a bonsai.json file and add the assets to the collection.
     *
     * @param  string  $path
     * @return Assets
     */
    protected function parseBonsai($path)
    {
        $file   = file_get_contents($path);
        $assets = json_decode($file, true);

        foreach ($assets as $path => $meta) {
            $namespace = (isset($meta['namespace'])) ? $meta['namespace'] : null;

            $asset = $this->addAsset($path, $namespace);

            if (isset($meta['dependency'])) {
                $asset->dependsOn($meta['dependency']);
            }
        }

        return;
    }

    /**
     * Sorts the dependencies of all assets, to ensure dependant
     * assets are loaded first.
     *
     * @param  array  $assets
     * @return array
     */
    protected function sortDependencies($assets = array(), $type = null)
    {
        $dependencyList = array();

        foreach ($assets as $key => $value) {
            if (isset($value['dependency'])) {
                $dependencyList[$key] = array($this->getNamespacedAsset($value['dependency'], $type));
            } else {
                $dependencyList[$key] = null;
            }
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
            if ($value['namespace'] === $namespace) {
                return $key;
            }
        }
    }
}

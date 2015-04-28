<?php
namespace Caffeinated\Bonsai;

use Illuminate\Support\Collection;

class Assets
{
	/**
	 * Regex pattern to match CSS/JS assets.
	 *
	 * @var string
	 */
	protected $assetRegex = '/.\.(css|js)$/i';

	/**
	 * Regex pattern to match CSS assets.
	 *
	 * @var string
	 */
	protected $cssRegex = '/.\.css$/i';

	/**
	 * Regex pattern to match JS assets.
	 *
	 * @var string
	 */
	protected $jsRegex = '/.\.js$/i';

	/**
	 * Regex pattern to match Bonsai json files.
	 *
	 * @var string
	 */
	protected $bonsaiRegex = '/bonsai\.json$/i';

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
		} elseif ($this->isAsset($assets)) {
			$this->addAsset($assets, $namespace);
		} elseif ($this->isBonsai($assets)) {
			$this->parseBonsai($assets);
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
		$cssCollection = $this->sortDependencies($this->collection->get('css'), 'css');
		$output        = '';

		foreach ($cssCollection as $css => $meta) {
			$output .= '<link rel="stylesheet" href="'.$css.'">'."\n";
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

		foreach ($jsCollection as $js => $meta) {
			$output .= '<script type="text/javascript" src="'.$js.'"></script>'."\n";
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
		return preg_match($this->assetRegex, $asset);
	}

	/**
	 * Determines if the passed asset is a Javascript file.
	 *
	 * @param  string  $asset
	 * @return bool
	 */
	protected function isJs($asset)
	{
		return preg_match($this->jsRegex, $asset);
	}

	/**
	 * Determines if the passed asset is a CSS file.
	 *
	 * @param  string  $asset
	 * @return bool
	 */
	protected function isCss($asset)
	{
		return preg_match($this->cssRegex, $asset);
	}

	/**
	 * Determines if the passed asset is a Bonsai JSON file.
	 *
	 * @param  string  $asset
	 * @return bool
	 */
	protected function isBonsai($asset)
	{
		return preg_match($this->bonsaiRegex, $asset);
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

		if (! in_array($assets, $collection)) {
			$collection[$assets] = array(
				'namespace' => $namespace
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
	protected function sortDependencies($assets = array(), $type)
	{
		$dependencies = array();

		foreach ($assets as $key => $value) {
			if (isset($value['dependency'])) {
				$dependencies[$key] = $this->getNamespacedAsset($value['dependency'], $type);
			} else {
				$dependencies[$key] = null;
			}
		}

		$hasCircularReferences = $this->hasCircularReferences($dependencies);

		if (! $hasCircularReferences) {
			array_multisort($dependencies, SORT_ASC, $assets);
		} else {
			throw new \Exception('Circular Reference Error.');
		}

		return $assets;
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

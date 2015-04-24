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
	public function add($assets)
	{
		if (is_array($assets)) {
			foreach ($assets as $asset) {
				$this->add($asset);
			}
		} elseif ($this->isAsset($assets)) {
			$this->addAsset($assets);
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
		return $this;
	}

	/**
	 * Builds the CSS HTML tags.
	 *
	 * @return Closure|string
	 */
	public function css()
	{
		$cssCollection = $this->collection->get('css');
		$output        = '';

		foreach ($cssCollection as $css) {
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
		$jsCollection = $this->collection->get('js');
		$output       = '';

		foreach ($jsCollection as $js) {
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
	protected function addAsset($assets)
	{
		if (is_array($assets)) {
			foreach ($assets as $asset) {
				$this->addAsset($asset);
			}

			return $this;
		}

		$type       = ($this->isCss($assets)) ? 'css' : 'js';
		$collection = $this->collection->get($type);

		if (! in_array($assets, $collection)) {
			$collection[] = $assets;

			$this->collection->put($type, $collection);
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
		$assets = json_decode($file);

		return $this->addAsset($assets);
	}
}

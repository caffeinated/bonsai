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
		} elseif ($this->isJs($assets)) {
			$this->addJs($assets);
		} elseif ($this->isCss($assets)) {
			$this->addCss($assets);
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
	 * Add a CSS asset file.
	 *
	 * @param  array|string  $assets
	 * @return Bonsai
	 */
	protected function addCss($assets)
	{
		if (is_array($assets)) {
			foreach ($assets as $asset) {
				$this->addCss($asset);
			}

			return $this;
		}

		$cssCollection = $this->collection->get('css');

		if (! in_array($assets, $cssCollection)) {
			$cssCollection[] = $assets;

			$this->collection->put('css', $cssCollection);
		}

		return $this;
	}

	/**
	 * Add a JS asset file.
	 *
	 * @param  array|string  $assets
	 * @return Bonsai
	 */
	protected function addJs($assets)
	{
		if (is_array($assets)) {
			foreach ($assets as $asset) {
				$this->addJs($asset);
			}

			return $this;
		}

		$jsCollection = $this->collection->get('js');

		if (! in_array($assets, $jsCollection)) {
			$jsCollection[] = $assets;

			$this->collection->put('js', $jsCollection);
		}

		return $this;
	}
}

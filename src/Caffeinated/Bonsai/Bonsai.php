<?php
namespace Caffeinated\Bonsai;

use Illuminate\Support\Collection;
use Illuminate\View\Factory;

class Bonsai
{
	/**
	 * @var Illuminate\Support\Collection
	 */
	protected $collection;

	/**
	 * @var \Illuminate\View\Factory
	 */
	protected $view;

	/**
	 * Constructor method.
	 *
	 * @return null
	 */
	public function __construct(Factory $view)
	{
		$this->collection = new Collection;
		$this->view       = $view;
	}

	/**
	 * Plant a new bonsai collection!
	 *
	 * @param  string  $namespace
	 * @param  string  $path
	 * @return null
	 */
	public function plant($callback)
	{
		if (is_callable($callback)) {
			$assets = new Assets;

			call_user_func($callback, $assets);

			$this->collection->put('bonsai', $assets);

			$this->view->share('bonsai', $assets);

			return $assets;
		}
	}

	/**
	 * Find and return the given bonsai collection.
	 *
	 * @return Illuminate\Support\Collection
	 */
	public function get()
	{
		return $this->collection->get('bonsai');
	}

	/**
	 * Add assets to a pre-planted Bonsai collection.
	 *
	 * @param  array|string  $assets
	 * @return Assets
	 */
	public function add($assets)
	{
		$bonsai = $this->get();

		return $bonsai->add($assets);
	}
}

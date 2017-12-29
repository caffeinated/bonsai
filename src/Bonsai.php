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
    public function plant($callback = null)
    {
        $assets = new Assets;

        if (is_callable($callback)) {
            call_user_func($callback, $assets);
        }

        $this->collection->put('bonsai', $assets);

        $this->view->share('bonsai', $assets);

        return $assets;
    }

    /**
     * Get and return the bonsai collection.
     *
     * @return mixed
     */
    public function get()
    {
        return $this->collection->get('bonsai', null);
    }

    /**
     * Add assets to a pre-planted Bonsai collection.
     *
     * @param  array|string  $assets
     * @return mixed
     */
    public function add($assets)
    {
        $bonsai = $this->get();

        if (! is_null($bonsai)) {
            return $bonsai->add($assets);
        }
    }
}

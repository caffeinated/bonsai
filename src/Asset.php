<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Caffeinated\Bonsai;

use Assetic\Asset\FileAsset;
use Assetic\Filter\FilterInterface;
use Assetic\Filter\HashableInterface;
use Caffeinated\Beverage\Contracts\Dependable;

/**
 * This is the Asset.
 *
 * @package        Caffeinated\Bonsai
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class Asset extends FileAsset implements Dependable
{
    /**
     * @var string
     */
    protected $handle;

    /**
     * @var array
     */
    protected $dependencies;

    /**
     * @var string
     */
    protected $ext;

    /** Instantiates the class
     *
     * @param string $handle
     * @param string $path
     * @param array  $dependencies
     * @internal param array $name
     * @internal param array $filters
     * @internal param null $sourceRoot
     * @internal param null $sourcePath
     * @internal param array $vars
     */
    public function __construct($handle, $path, array $dependencies = [ ])
    {
        parent::__construct($path);
        $this->handle       = $handle;
        $this->dependencies = $dependencies;
    }

    /**
     * Get the value of ext
     *
     * @return mixed
     */
    public function getExt()
    {
        return pathinfo($this->getSourcePath(), PATHINFO_EXTENSION);
    }

    /**
     * get dependencies
     *
     * @return array
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * setDependencies
     *
     * @param array $dependencies
     */
    public function setDependencies(array $dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * get item key/identifier
     *
     * @return string|mixed
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * getCacheKey
     *
     * @return string
     */
    public function getCacheKey()
    {
        $key = $this->handle . $this->getSourcePath();
        foreach ($this->getFilters() as $filter) {
            $key .= $filter instanceof HashableInterface ? $filter->hash() : serialize($filter);
        }

        return $key;
    }

    /**
     * load
     *
     * @param \Assetic\Filter\FilterInterface|null $additionalFilter
     * @return $this
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        parent::load($additionalFilter);

        return $this;
    }

    /**
     * ensureFilter
     *
     * @param \Assetic\Filter\FilterInterface $filter
     * @return $this
     */
    public function ensureFilter(FilterInterface $filter)
    {
        parent::ensureFilter($filter);

        return $this;
    }
}

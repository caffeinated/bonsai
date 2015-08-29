<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Caffeinated\Tests\Bonsai;

use Caffeinated\Beverage\Path;
use Caffeinated\Bonsai\AssetGroup;
use Mockery as m;

/**
 * This is the AssetGroupTest.
 *
 * @package        Caffeinated\Tests
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class AssetGroupTest extends TestCase
{
    /**
     * @var \Mockery\MockInterface
     */
    protected $bonsai;

    /**
     * @var \Mockery\MockInterface
     */
    protected $cache;

    /**
     * @var \Mockery\MockInterface
     */
    protected $files;

    protected $assetClass = 'Caffeinated\Bonsai\Asset';

    public function setUp()
    {
        parent::setUp();
        $this->bonsai = m::mock('Caffeinated\Bonsai\Contracts\Factory');
        $this->cache  = m::mock('Illuminate\Contracts\Cache\Factory');
        $this->files  = m::mock('Illuminate\Contracts\Filesystem\Filesystem');
    }

    public function _createAssetGroup($name = 'testgroup')
    {
        return new AssetGroup($this->bonsai, $this->cache, $this->files, $name);
    }

    public function _createAssetGroupAndAddAsset($handle, $path, $name = 'testgroup')
    {
        $group = $this->_createAssetGroup();
        $this->bonsai->shouldReceive('make')->with($handle, $path)->andReturn($asset = m::mock($this->assetClass));
        $asset->shouldReceive('getExt')->andReturn(Path::getExtension($path, true));
        $asset->shouldReceive('setDependencies')->andReturn();
        $group->add($handle, $path);

        return $group;
    }

    /** Instantiates the class */
    public function testAddsAndGetsAssets()
    {
        $group = $this->_createAssetGroupAndAddAsset($handle = 'asset1', $path = 'assets/myasset/js.js');
        $this->assertTrue($group->has('script', $handle));
        $this->assertFalse($group->has('script', 'asset2'));
        $asset = $group->get('script', $handle);
        $this->assertArrayHasKey('handle', $asset);
        $this->assertArrayHasKey('asset', $asset);
        $this->assertArrayHasKey('type', $asset);
        $this->assertArrayHasKey('depends', $asset);
        $this->assertInstanceOf($this->assetClass, $asset[ 'asset' ]);
    }

    public function testCreatesAssetGroup()
    {
        $group = $this->_createAssetGroup('mytestgroup');
        $this->assertInstanceOf(AssetGroup::class, $group);
    }
}

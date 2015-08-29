<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Caffeinated\Tests\Bonsai;

/**
 * This is the BonsaiTest.
 *
 * @package        Caffeinated\Tests
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class FactoryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testSomething()
    {
        $this->assertEquals(true, true);
        $this->registerBeverageServiceProvider();
        /**
         * @var \Caffeinated\Bonsai\Factory $bonsai
         */

        $themes = $this->app->make('caffeinated.themes');
        $bonsai = $this->app->make('caffeinated.bonsai');

        $asset = $bonsai->make('some-asset', '');


        $a='a';
    }
}

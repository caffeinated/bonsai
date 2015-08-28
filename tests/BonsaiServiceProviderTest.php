<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Caffeinated\Tests\Bonsai;

use Caffeinated\Dev\Testing\Traits\ServiceProviderTester;

/**
 * This is the BeverageServiceProviderTest.
 *
 * @package        Caffeinated\Tests
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class BeverageServiceProviderTest extends TestCase
{
    use ServiceProviderTester;

    public function testConfigFiles()
    {
       # $this->registerBeverageServiceProvider();
       # $this->runServiceProviderPublishesConfigTest([ 'caffeinated.beverage']);
    }
}

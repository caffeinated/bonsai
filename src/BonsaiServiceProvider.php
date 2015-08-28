<?php
/**
* Part of the Caffeinated PHP packages.
*
* MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Caffeinated\Bonsai;

use Caffeinated\Beverage\ServiceProvider;

/**
 * This is the BonsaiServiceProvider.
 *
 * @package        Caffeinated\Bonsai
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class BonsaiServiceProvider extends ServiceProvider
{
    protected $dir = __DIR__;

    protected $provides = [ 'bonsai' ];

    protected $bindings = [
        'bonsai' => \Caffeinated\Bonsai\Bonsai::class
    ];


}

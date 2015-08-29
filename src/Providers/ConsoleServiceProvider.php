<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Caffeinated\Bonsai\Providers;

use Caffeinated\Beverage\ConsoleServiceProvider as BaseConsoleProvider;

/**
 * This is the ConsoleServiceProvider.
 *
 * @package        Caffeinated\Bonsai
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class ConsoleServiceProvider extends BaseConsoleProvider
{
    /**
     * The namespace where the commands are
     *
     * @var string
     */
    protected $namespace = 'Caffeinated\\Bonsai\\Console';

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'test' => 'BonsaiTest'
    ];

    /**
     * @var string
     */
    protected $prefix = 'caffeinated.bonsai.';
}

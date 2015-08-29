<?php
/**
 * Part of the Caffeinated PHP packages.
 *
 * MIT License and copyright information bundled with this package in the LICENSE file
 */
namespace Caffeinated\Bonsai\Console;

use Caffeinated\Beverage\Command;

/**
 * This is the BonsaiTestCommand.
 *
 * @package        Caffeinated\Themes
 * @author         Caffeinated Dev Team
 * @copyright      Copyright (c) 2015, Caffeinated
 * @license        https://tldrlegal.com/license/mit-license MIT License
 */
class BonsaiTestCommand extends Command
{

    protected $signature = 'bonsai:test';

    protected $description = 'Placeholder command ';

    public function handle()
    {
        $this->comment('Bonsai test command, at your service');

        $themes = $this->getLaravel()->make('caffeinated.themes');

        /**
         * @var \Caffeinated\Bonsai\Factory $bonsai
         */
        $bonsai = $this->getLaravel()->make('caffeinated.bonsai');

        $a = 'a';
    }
}

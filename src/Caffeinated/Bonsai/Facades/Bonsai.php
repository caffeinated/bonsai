<?php
namespace Caffeinated\Bonsai\Facades;

use Illuminate\Support\Facades\Facade;

class Bonsai extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'bonsai';
	}
}
<?php
namespace Caffeinated\Bonsai;

class Node
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var array
	 */
	public $children = array();

	/**
	 * @var array
	 */
	public $parents = array();

	/**
	 * Constructor method.
	 *
	 * @param  string  $name
	 */
	public function __construct($name = '')
	{
		$this->name = $name;
	}
}
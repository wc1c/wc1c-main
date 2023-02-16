<?php namespace Wc1c\Main\Traits;

defined('ABSPATH') || exit;

/**
 * CoreTrait
 *
 * @package Wc1c\Main\Traits
 */
trait CoreTrait
{
	/**
	 * @return
	 */
	public function core()
	{
		return $this->core;
	}

	/**
	 * @param $core
	 */
	public function setCore($core)
	{
		$this->core = $core;
	}
}
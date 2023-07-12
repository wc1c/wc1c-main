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
     * @var mixed
     */
    public $core;

	/**
	 * @return mixed
	 */
	public function core()
	{
		return $this->core;
	}

	/**
	 * @param mixed $core
	 */
	public function setCore($core)
	{
		$this->core = $core;
	}
}
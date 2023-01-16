<?php namespace Digiom\Woplucore\Abstracts;

defined('ABSPATH') || exit;

/**
 * ScreenAbstract
 *
 * @package Digiom\Woplucore\Abstracts
 */
abstract class ScreenAbstract
{
	/**
	 * @var string
	 */
	public $prefix = 'plugin';

	/**
	 * ScreenAbstract constructor.
	 */
	public function __construct()
	{
		add_action($this->prefix . '_admin_show', [$this, 'output'], 10);
	}

	/**
	 * @return mixed
	 */
	abstract public function output();

	/**
	 * @return string
	 */
	public function getPrefix(): string
	{
		return $this->prefix;
	}

	/**
	 * @param string $prefix
	 */
	public function setPrefix(string $prefix)
	{
		$this->prefix = $prefix;
	}
}
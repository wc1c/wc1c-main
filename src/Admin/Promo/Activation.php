<?php namespace Wc1c\Main\Admin\Promo;

defined('ABSPATH') || exit;

use Wc1c\Main\Abstracts\ScreenAbstract;
use Wc1c\Main\Traits\SingletonTrait;

/**
 * Activation
 *
 * @package Wc1c\Main\Admin\Promo
 */
final class Activation extends ScreenAbstract
{
	use SingletonTrait;

	/**
	 * Initialized
	 */
	public function init()
	{
	}

	/**
	 * Output tools table
	 *
	 * @return void
	 */
	public function output()
	{
		$args['object'] = $this;

		wc1c()->views()->getView('promo/activation.php', $args);
	}
}
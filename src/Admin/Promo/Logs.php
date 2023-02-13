<?php namespace Wc1c\Main\Admin\Promo;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;
use Wc1c\Main\Admin\Traits\ProcessConfigurationTrait;

/**
 * Logs
 *
 * @package Wc1c\Main\Admin\Promo
 */
final class Logs
{
	use SingletonTrait;
    use ProcessConfigurationTrait;

	/**
	 * Initialized
	 */
	public function process()
	{
        add_action('wc1c_admin_configurations_update_show', [$this, 'output'], 10);
	}

	/**
	 * Output tools table
	 *
	 * @return void
	 */
	public function output()
	{
		$args['object'] = $this;

		wc1c()->views()->getView('promo/logs.php', $args);
	}
}
<?php
/**
 * Plugin Name: WC1C Main
 * Plugin URI: https://wordpress.org/plugins/wc1c-main/
 * Description: Implementation of a mechanism for flexible exchange of various data between 1C products and a site running WordPress using the WooCommerce plugin.
 * Version: 0.14.7
 * WC requires at least: 4.3
 * WC tested up to: 7.2
 * Requires at least: 5.2
 * Requires PHP: 7.0
 * Requires Plugins: woocommerce
 * Text Domain: wc1c-main
 * Domain Path: /assets/languages
 * Copyright: WC1C team © 2018-2022
 * Author: WC1C team
 * Author URI: https://wc1c.info
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 **/
namespace
{
	defined('ABSPATH') || exit;

	if(version_compare(PHP_VERSION, '7.0') < 0)
	{
		return false;
	}

	if(false === defined('WC1C_PLUGIN_FILE'))
	{
		define('WC1C_PLUGIN_FILE', __FILE__);

		include_once __DIR__ . '/vendor/autoload.php';

		/**
		 * Main instance of WC1C
		 *
		 * @return Wc1c\Main\Core
		 */
		function wc1c(): Wc1c\Main\Core
		{
			return Wc1c\Main\Core::instance();
		}
	}
}

/**
 * @package Wc1c\Main
 */
namespace Wc1c\Main
{
	$loader = new \Digiom\Woplucore\Loader();

	try
	{
		$loader->addNamespace(__NAMESPACE__, plugin_dir_path(__FILE__) . 'src');

		$loader->register(__FILE__);

		$loader->registerActivation([Activation::class, 'instance']);
		$loader->registerDeactivation([Deactivation::class, 'instance']);
		$loader->registerUninstall([Uninstall::class, 'instance']);
	}
	catch(\Exception $e)
	{
		trigger_error($e->getMessage());
	}

	wc1c()->register(new Context(), $loader);
}
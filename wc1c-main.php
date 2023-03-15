<?php
/**
 * Plugin Name: WC1C
 * Plugin URI: https://wordpress.org/plugins/wc1c-main/
 * Description: Implementation of a mechanism for flexible exchange of various data between 1C products and the WooCommerce plugin.
 * Version: 0.19.1
 * WC requires at least: 4.3
 * WC tested up to: 7.5
 * Requires at least: 5.2
 * Requires PHP: 7.0
 * Requires Plugins: woocommerce
 * Text Domain: wc1c-main
 * Domain Path: /assets/languages
 * Copyright: WC1C team © 2018-2023
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

		$autoloader = __DIR__ . '/vendor/autoload.php';

		if(!is_readable($autoloader))
		{
			trigger_error('File is not found: ' . $autoloader);
			return false;
		}

		require_once $autoloader;

        /**
         * For external use
         *
         * @return Wc1c\Main\Core Main instance of core
         */
		function wc1c(): Wc1c\Main\Core
		{
			return Wc1c\Main\Core();
		}
	}
}

/**
 * @package Wc1c\Main
 */
namespace Wc1c\Main
{
    /**
     * For internal use
     *
     * @return Core Main instance of plugin core
     */
    function core(): Core
    {
        return Core::instance();
    }

	$loader = new \Digiom\Woplucore\Loader();

	try
	{
		$loader->addNamespace(__NAMESPACE__, plugin_dir_path(__FILE__) . 'src');

		$loader->register(__FILE__);

		$loader->registerActivation([Activation::class, 'instance']);
		$loader->registerDeactivation([Deactivation::class, 'instance']);
		$loader->registerUninstall([Uninstall::class, 'instance']);
	}
	catch(\Throwable $e)
	{
		trigger_error($e->getMessage());
		return false;
	}

	$context = new Context(__FILE__, 'wc1c', $loader);

	wc1c()->register($context);
}
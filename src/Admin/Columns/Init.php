<?php namespace Wc1c\Main\Admin\Columns;

defined('ABSPATH') || exit;

use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Admin\Columns\WooCommerce\Categories;
use Wc1c\Main\Admin\Columns\WooCommerce\Orders;
use Wc1c\Main\Admin\Columns\WooCommerce\Products;
use Wc1c\Main\Admin\Columns\WordPress\MediaLibrary;

/**
 * Init
 *
 * @package Wc1c\Main\Admin
 */
final class Init
{
	use SingletonTrait;

	/**
	 * Init constructor.
	 */
	public function __construct()
	{
		if('yes' === wc1c()->settings('interface')->get('admin_interface_products_column', 'yes'))
		{
			Products::instance();
		}

		if('yes' === wc1c()->settings('interface')->get('admin_interface_orders_column', 'yes'))
		{
			Orders::instance();
		}

		if('yes' === wc1c()->settings('interface')->get('admin_interface_categories_column', 'yes'))
		{
			Categories::instance();
		}

		if('yes' === wc1c()->settings('interface')->get('admin_interface_media_library_column', 'yes'))
		{
			MediaLibrary::instance();
		}
	}
}
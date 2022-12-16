<?php namespace Wc1c\Main\Admin\Metaboxes;

defined('ABSPATH') || exit;

use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Admin\Metaboxes\WooCommerce\Orders;
use Wc1c\Main\Admin\Metaboxes\WooCommerce\Products;

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
		if('yes' === wc1c()->settings('interface')->get('admin_interface_products_edit_metabox', 'yes'))
		{
			Products::instance();
		}

		if('yes' === wc1c()->settings('interface')->get('admin_interface_orders_edit_metabox', 'yes'))
		{
			Orders::instance();
		}
	}
}
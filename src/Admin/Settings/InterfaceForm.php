<?php namespace Wc1c\Main\Admin\Settings;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Settings\InterfaceSettings;

/**
 *  InterfaceForm
 *
 * @package Wc1c\Main\Admin
 */
class InterfaceForm extends Form
{
	/**
	 * InterfaceForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->set_id('settings-interface');
		$this->setSettings(new InterfaceSettings());

		add_filter('wc1c_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_interface'], 10);

		$this->init();
	}
	/**
	 * Add for Interface
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_interface($fields)
	{
		$fields['admin_interface'] =
		[
			'title' => __('Changing the interface', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Allow changes to WordPress and WooCommerce dashboard interface?', 'wc1c-main'),
			'description' => sprintf
			(
				'%s <hr>%s',
				__('If enabled, new features will appear in the WordPress and WooCommerce interface according to the interface change settings.', 'wc1c-main'),
				__('If interface modification is enabled, it is possible to change settings for individual features, users, and roles. If disabled, features will be disabled globally for everyone and everything.', 'wc1c-main')
			),
			'default' => 'yes'
		];

		$fields['interface_title_woocommerce'] =
		[
			'title' => __('WooCommerce', 'wc1c-main'),
			'type' => 'title',
			'description' => __('Some interface settings for the WooCommerce.', 'wc1c-main'),
		];

		$fields['admin_interface_products_column'] =
		[
			'title' => __('Column in products list', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wc1c-main'),
			'description' => __('Output of a column with information from 1C to the list of products.', 'wc1c-main'),
			'default' => 'yes'
		];

		$fields['admin_interface_products_edit_metabox'] =
		[
			'title' => __('Metabox in edit products', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wc1c-main'),
			'description' => __('Output of a Metabox with information from 1C in edit products.', 'wc1c-main'),
			'default' => 'yes'
		];

		$fields['admin_interface_orders_column'] =
		[
			'title' => __('Column in orders list', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wc1c-main'),
			'description' => __('Output of a column with information from 1C to the list of orders.', 'wc1c-main'),
			'default' => 'yes'
		];

		$fields['admin_interface_orders_edit_metabox'] =
		[
			'title' => __('Metabox in edit orders', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wc1c-main'),
			'description' => __('Output of a Metabox with information from 1C in edit orders.', 'wc1c-main'),
			'default' => 'yes'
		];

		$fields['admin_interface_categories_column'] =
		[
			'title' => __('Column in categories list', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wc1c-main'),
			'description' => __('Output of a column with information from 1C to the list of categories.', 'wc1c-main'),
			'default' => 'yes'
		];

		$fields['interface_title_wordpress'] =
		[
			'title' => __('WordPress', 'wc1c-main'),
			'type' => 'title',
			'description' => __('Some interface settings for the WordPress.', 'wc1c-main'),
		];

		$fields['admin_interface_media_library_column'] =
		[
			'title' => __('Column in media library list', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Enable', 'wc1c-main'),
			'description' => __('Output of a column with information from 1C to the list of media files.', 'wc1c-main'),
			'default' => 'yes'
		];

		return $fields;
	}
}
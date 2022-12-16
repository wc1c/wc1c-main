<?php namespace Wc1c\Main\Admin\Columns\WooCommerce;

defined('ABSPATH') || exit;

use Wc1c\Main\Traits\SingletonTrait;

/**
 * Products
 *
 * @package Wc1c\Main\Admin
 */
final class Products
{
	use SingletonTrait;

	/**
	 * Products constructor.
	 */
	public function __construct()
	{
		add_filter('manage_edit-product_columns',  [$this, 'manage_edit_product_columns']);
		add_filter('wc1c_admin_interface_products_lists_column', [$this, 'wc1c_admin_interface_products_lists_column'], 10, 2);

		add_action('manage_product_posts_custom_column', [$this, 'manage_product_posts_custom_column'], 10, 2);
	}

	/**
	 * Adding a column to the list of products for displaying 1C information
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function manage_edit_product_columns(array $columns): array
	{
		$columns_after =
		[
			'wc1c' => __('1C information', 'wc1c-main'),
		];

		return array_merge($columns, $columns_after);
	}

	/**
	 * Information from 1C in products list
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function manage_product_posts_custom_column($column, $post_id)
	{
		if('wc1c' === $column)
		{
			$content = '';

			if(has_filter('wc1c_admin_interface_products_lists_column'))
			{
				$content = apply_filters('wc1c_admin_interface_products_lists_column', $content, $post_id);
			}

			if('' === $content)
			{
				$content .= '<span class="na">' . __('Not found', 'wc1c-main') . '</span>';
			}

			echo $content;
		}
	}

	/**
	 *
	 * @param $content
	 * @param $post_id
	 *
	 * @return string
	 */
	public function wc1c_admin_interface_products_lists_column($content, $post_id): string
	{
		$schema_id = get_post_meta($post_id, '_wc1c_schema_id', true);
		$config_id = get_post_meta($post_id, '_wc1c_configuration_id', true);

		if($schema_id)
		{
			$content .= '<span class="na">' . __('Schema ID: ', 'wc1c-main') . $schema_id . '</span>';
		}

		if($config_id)
		{
			$content .= '<br/><span class="na">' . __('Configuration ID: ', 'wc1c-main')  . $config_id . '</span>';
		}

		return $content;
	}
}
<?php namespace Wc1c\Main\Admin\Columns\WooCommerce;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Wc\Entities\Category;

/**
 * Categories
 *
 * @package Wc1c\Main\Admin
 */
final class Categories
{
	use SingletonTrait;

	/**
	 * Categories constructor.
	 */
	public function __construct()
	{
		add_filter('manage_edit-product_cat_columns', [$this, 'manage_edit_taxonomy_columns']);
		add_filter('manage_product_cat_custom_column', [$this, 'manage_taxonomy_custom_column'], 10, 3);
	}

	/**
	 * Adding a column to the categories for displaying 1C information
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function manage_edit_taxonomy_columns($columns)
	{
		$columns_after =
		[
			'wc1c' => __('1C information', 'wc1c-main'),
		];

		return array_merge($columns, $columns_after);
	}

	/**
	 * Information from 1C in categories list
	 *
	 * @param $columns
	 * @param $column
	 * @param $id
	 *
	 * @return string
	 */
	public function manage_taxonomy_custom_column($columns, $column, $id)
	{
		if('wc1c' === $column)
		{
			$content = '';

			if(has_filter('wc1c_admin_interface_categories_column'))
			{
				$content = apply_filters('wc1c_admin_interface_categories_column', $content, $columns, $id);
			}
			else
			{
				try
				{
					$category = new Category($id);
				}
				catch(Exception $e)
				{
					return $columns;
				}

				$schema_id = $category->getSchemaId();
				$config_id = $category->getConfigurationId();

				if($schema_id)
				{
					$content .= '<span class="na">' . __('Schema ID: ', 'wc1c-main') . $schema_id . '</span>';
				}

				if($config_id)
				{
					$content .= '<br/><span class="na">' . __('Configuration ID: ', 'wc1c-main')  . $config_id . '</span>';
				}
			}

			if('' === $content)
			{
				$content = '<span class="na">' . __('Not found', 'wc1c-main') . '</span>';
			}

			$columns .= $content;
		}

		return $columns;
	}
}
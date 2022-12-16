<?php namespace Wc1c\Wc\Storages;

defined('ABSPATH') || exit;

use WP_Error;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Wc\Contracts\AttributeContract;
use Wc1c\Wc\Contracts\AttributesStorageContract;
use Wc1c\Wc\Contracts\StorageContract;
use Wc1c\Wc\Entities\Attribute;

/**
 * AttributesStorage
 *
 * @package Wc1c\Wc
 */
class AttributesStorage implements AttributesStorageContract, StorageContract
{
	/**
	 * Method to create a new object in the database
	 *
	 * @param AttributeContract $data Data object
	 */
	public function create(&$data)
	{
		$name = $data->getName() ? $this->getUniqueName($data->getName()) : $this->getUniqueName($data->getLabel());

		$args =
		[
			'name' => $data->getLabel(),
			'slug' => $name,
			'type' => $data->getType(),
			'order_by' => $data->getOrder(),
			'has_archives' => $data->getPublic(),
		];

		$attribute_id = wc_create_attribute($args);

		if(is_wp_error($attribute_id))
		{
			$attribute_id = new WP_Error('db_insert_error', __('Could not insert into the database.'), $attribute_id->get_error_message());
		}

		if($attribute_id && !is_wp_error($attribute_id))
		{
			$data->setId($attribute_id);
			$data->setName($name);

			$data->saveMetaData();
			$data->applyChanges();

			// hook
			do_action('wc1c_wc_data_storage_attribute_create', $attribute_id, $data);
		}
	}

	/**
	 * Method to read an object from the database
	 *
	 * @param AttributeContract $data Data object
	 *
	 * @throws Exception If invalid attribute
	 */
	public function read(&$data)
	{
		$data->setDefaults();

		if(!$data->getId())
		{
			throw new Exception(__('Invalid attribute.', 'wc1c'));
		}

		$taxonomies = wc_get_attribute_taxonomies();

		$array_key = 'id:' . $data->getId();

		if(!isset($taxonomies[$array_key]))
		{
			throw new Exception(__('Invalid taxonomy.', 'wc1c'));
		}

		$data->setProps
		(
			[
				'name' => $taxonomies[$array_key]->attribute_name,
				'label'=> $taxonomies[$array_key]->attribute_label,
				'type' => $taxonomies[$array_key]->attribute_type,
				'public' => $taxonomies[$array_key]->attribute_public,
				'order' => $taxonomies[$array_key]->attribute_orderby,
			]
		);

		$data->setObjectRead(true);

		do_action('wc1c_wc_data_storage_attribute_read', $data->getId());
	}

	/**
	 * Method to update a data in the database
	 *
	 * @param AttributeContract $data Data object
	 */
	public function update(&$data)
	{
		$data->saveMetaData();

		$changes = $data->getChanges();

		// Only changed update data changes
		if
		(
			array_intersect
			(
				[
					'name',
					'label',
					'type',
					'public',
					'order',
				],
				array_keys($changes)
			)
		)
		{
			$args =
			[
				'name' => $data->getLabel(),
				'slug' => $data->getName(),
				'type' => $data->getType(),
				'order_by' => $data->getOrder(),
				'has_archives' => $data->getPublic(),
			];

			wc_update_attribute($data->getId(), $args); // todo: error?

			$data->readMetaData();
		}

		$data->applyChanges();

		do_action('wc1c_wc_data_storage_attribute_update', $data->getId(), $data);
	}

	/**
	 * Method to delete an object from the database
	 *
	 * @param AttributeContract $attribute Data object
	 * @param array $args Array of args to pass to the delete method
	 */
	public function delete(&$attribute, $args = [])
	{
		$object_id = $attribute->getId();

		if(!$object_id)
		{
			return;
		}

		do_action('wc1c_wc_data_storage_attribute_before_delete', $object_id);

		wc_delete_attribute($object_id);

		$attribute->setId(0);

		do_action('wc1c_wc_data_storage_attribute_after_delete', $object_id);
	}

	/**
	 * Получение атрибута по этикетке атрибута из WooCommerce
	 *
	 * @param string $label Этикетка искомого атрибута
	 *
	 * @return false|AttributeContract
	 */
	public function getByLabel($label)
	{
		$taxonomies = wp_list_pluck(wc_get_attribute_taxonomies(), 'attribute_id', 'attribute_label');

		if(!isset($taxonomies[$label]))
		{
			return false;
		}

		$id = (int) $taxonomies[$label];

		try
		{
			$attribute = new Attribute($id);
		}
		catch(Exception $e)
		{
			return false;
		}

		return $attribute;
	}

	/**
	 * Получение атрибута по наименованию атрибута из WooCommerce
	 *
	 * @param string $name Наименование искомого атрибута
	 *
	 * @return false|AttributeContract
	 */
	public function getByName($name)
	{
		$taxonomies = wp_list_pluck(wc_get_attribute_taxonomies(), 'attribute_id', 'attribute_name');

		if(!isset($taxonomies[$name]))
		{
			return false;
		}

		$id = (int) $taxonomies[$name];

		try
		{
			$attribute = new Attribute($id);
		}
		catch(Exception $e)
		{
			return false;
		}

		return $attribute;
	}

	/**
	 * Получение уникального слага для атрибута
	 *
	 * @param $label
	 *
	 * @return false|string
	 */
	public function getUniqueName($label)
	{
		$name = \wc_sanitize_taxonomy_name($label);

		// https://developer.wordpress.org/reference/functions/register_taxonomy/
		$maxNameLength = 32;

		// WooCommerce added prefix - `pa_`
		$maxNameLength -= 3;

		// count value up to 99 - `-00`
		$maxNameLength -= 3;

		if(strlen($name) > $maxNameLength)
		{
			$name = substr($name, 0, $maxNameLength);
		}

		/*
		 * the second call to clear a possible incorrect result,
		 * for example, it might get `opisanie-dlya-sluzhebnogo-`, but it should be `opisanie-dlya-sluzhebnogo`
		 */
		$name = wc_sanitize_taxonomy_name($name);
		$resolvedName = $name;
		$count = 0;
		$attribute = $this->getByName($resolvedName);

		while($attribute && $count < 1000)
		{
			$count++;
			$resolvedName = $name . '-' . $count;
			$attribute = $this->getByName($resolvedName);
		}

		if($count > 999)
		{
			return false;
		}

		return $resolvedName;
	}
}
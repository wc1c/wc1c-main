<?php namespace Wc1c\Wc\Storages;

defined('ABSPATH') || exit;

use stdClass;
use WP_Error;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Wc\Contracts\CategoriesStorageContract;
use Wc1c\Wc\Contracts\MetaStorageContract;
use Wc1c\Wc\Contracts\StorageContract;
use Wc1c\Wc\Entities\Category;

/**
 * CategoriesStorage
 *
 * @package Wc1c\Wc
 */
class CategoriesStorage implements CategoriesStorageContract, StorageContract, MetaStorageContract
{
	/**
	 * Method to create a new object in the database
	 *
	 * @param Category $data Data object
	 */
	public function create(&$data)
	{
		$category_result = wp_insert_term
		(
			$data->getName(),
			'product_cat',
			[
				'description' => $data->getDescription(),
				'slug' => $data->getSlug(),
				'parent' => (int)$data->getParentId('view')
			]
		);

		if(is_wp_error($category_result))
		{
			$object_id = new WP_Error('db_insert_error', __('Could not insert into the database.'), $category_result->get_error_message());
		}
		else
		{
			$object_id = $category_result['term_id'] ?? false;
		}

		if($object_id && !is_wp_error($object_id))
		{
			$data->setId($object_id);

			$data->saveMetaData();
			$data->applyChanges();

			// hook
			do_action('wc1c_wc_data_storage_category_create', $object_id, $data);
		}
	}

	/**
	 * Method to read an object from the database
	 *
	 * @param Category $data Data object
	 *
	 * @throws Exception If invalid category
	 */
	public function read(&$data)
	{
		$data->setDefaults();

		if(!$data->getId())
		{
			throw new Exception(__('Invalid category.', 'wc1c'));
		}

		$current_categories_query = get_term_by('id', $data->getId(), 'product_cat', ARRAY_A);

		if(!is_wp_error($current_categories_query) && isset($current_categories_query['name']))
		{
			$data->setProps
			(
				[
					'name' => $current_categories_query['name'],
					'description'=> $current_categories_query['description'],
					'parent_id' => (int)$current_categories_query['parent'],
					'slug' => $current_categories_query['slug'],
				]
			);
		}

		$data->setObjectRead(true);

		do_action('wc1c_wc_data_storage_category_read', $data->getId());
	}

	/**
	 * Method to update a data in the database
	 *
	 * @param Category $data Data object
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
					'description',
					'slug',
					'parent_id',
					'image_id',
					'display_type',
				],
				array_keys($changes)
			)
		)
		{
			$args =
			[
				'name' => $data->getName(),
				'description' => $data->getDescription(),
				'parent' => $data->getParentId('edit'),
				'slug' => $data->getSlug(),
			];

			wp_update_term($data->getId(), 'product_cat', $args);

			$data->readMetaData();
		}

		$data->applyChanges();

		do_action('wc1c_wc_data_storage_category_update', $data->getId(), $data);
	}

	/**
	 * Method to delete an object from the database
	 *
	 * @param Category $data Data object
	 * @param array $args Array of args to pass to the delete method
	 */
	public function delete(&$data, $args = [])
	{
		$object_id = $data->getId();

		if(!$object_id)
		{
			return;
		}

		$args = wp_parse_args
		(
			$args,
			[
				'force_delete' => false
			]
		);

		if($args['force_delete'])
		{
			do_action('wc1c_wc_data_storage_category_before_delete', $object_id);

			wp_delete_term($object_id, 'product_cat');

			$data->setId(0);

			do_action('wc1c_wc_data_storage_category_after_delete', $object_id);
		}
		else
		{
			do_action('wc1c_wc_data_storage_category_before_trash', $object_id);

			$data->addMetaData('status', 'deleted');
			$data->saveMetaData();

			do_action('wc1c_wc_data_storage_category_after_trash', $object_id);
		}
	}

	/**
	 * Get all categories by 1C id
	 *
	 * @param string|int $id
	 *
	 * @return false|Category|Category[]
	 * @throws Exception
	 */
	public function getByExternalId($id)
	{
		if(empty($id))
		{
			return false;
		}

		$args =
		[
			'hide_empty' => false,
			'meta_query' =>
			[
				[
					'key' => '_wc1c_external_id',
					'value' => $id,
					'compare' => 'LIKE'
				]
			],
			'taxonomy' => 'product_cat',
		];

		$terms = get_terms($args);

		if(is_array($terms))
		{
			$count = count($terms);

			if($count === 1)
			{
				return new Category($terms[0]->term_id);
			}

			$final = [];

			foreach($terms as $term)
			{
				$final[] = new Category($term->term_id);
			}

			return $final;
		}

		return false;
	}

	/**
	 * Getting all categories by name
	 *
	 * @param $name
	 *
	 * @return false|Category|Category[]
	 * @throws Exception
	 */
	public function getByName($name)
	{
		if(empty($name))
		{
			return false;
		}

		$args =
		[
			'hide_empty' => false,
			'taxonomy' => 'product_cat',
			'name' => $name,
		];

		$terms = get_terms($args);

		if(is_array($terms))
		{
			$count = count($terms);

			if($count === 1)
			{
				return new Category($terms[0]->term_id);
			}

			$final = [];

			foreach($terms as $term)
			{
				$final[] = new Category($term->term_id);
			}

			return $final;
		}

		return false;
	}

	public function getById($id)
	{
		// TODO: Implement getById() method.
	}

	/**
	 * Returns an array of meta for an object.
	 *
	 * @param Category $data Data object
	 *
	 * @return array
	 */
	public function readMeta(&$data): array
	{
		$raw_meta_data = get_term_meta($data->getId());

		return apply_filters('wc1c_wc_data_storage_category_meta_read', $raw_meta_data, $data, $this);
	}

	/**
	 * Deletes meta based on meta ID
	 *
	 * @param Category $data Data object
	 * @param stdClass $meta (containing at least -> id).
	 *
	 * @return bool
	 */
	public function deleteMeta(&$data, $meta): bool
	{
		if(!$meta->key || !is_numeric($data->getId()))
		{
			return false;
		}

		$meta_id = (int) $meta->id;
		if($meta_id <= 0)
		{
			return false;
		}

		// hook
		do_action('wc1c_wc_data_storage_category_meta_delete', [$meta_id, $data->getId(), $meta->key, $meta->value]);

		$result = delete_term_meta($meta_id, $meta->key, $meta->value);

		// hook
		do_action('wc1c_wc_data_storage_category_meta_deleted', [$meta_id, $data->getId(), $meta->key, $meta->value]);

		return $result;
	}

	/**
	 * Add new piece of meta
	 *
	 * @param Category $data Data object
	 * @param stdClass $meta (containing ->key and ->value)
	 *
	 * @return false|int meta ID
	 */
	public function addMeta(&$data, $meta)
	{
		if(!$meta->key || !is_numeric($data->getId()))
		{
			return false;
		}

		$meta_key = wp_unslash($meta->key);
		$meta_value = wp_unslash($meta->value);

		$_meta_value = $meta_value;
		$meta_value = maybe_serialize($meta_value);

		/**
		 * Fires immediately before meta of a specific type is added.
		 *
		 * @param int $object_id Object ID.
		 * @param string $meta_key Meta key.
		 * @param mixed $meta_value Meta value.
		 */
		do_action('wc1c_wc_data_storage_category_meta_add', $data->getId(), $meta_key, $_meta_value);

		$result = add_term_meta($data->getId(), $meta_key, $meta_value, false);

		if(!$result || is_wp_error($result))
		{
			return false;
		}

		$meta_id = (int) $result;

		/**
		 * Fires immediately after meta of a specific type is added
		 *
		 * @param int $meta_id The meta ID after successful update.
		 * @param int $object_id Object ID.
		 * @param string $meta_key Meta key.
		 * @param mixed $meta_value Meta value.
		 */
		do_action('wc1c_wc_data_storage_category_meta_added', $meta_id, $data->getId(), $meta_key, $_meta_value);

		return $meta_id;
	}

	/**
	 * Update meta
	 *
	 * @param Category $data Data object
	 * @param stdClass $meta (containing ->id, ->key and ->value).
	 *
	 * @return bool
	 */
	public function updateMeta(&$data, $meta): bool
	{
		if(!$meta->key || !is_numeric($data->getId()))
		{
			return false;
		}

		$meta_id = (int) $meta->id;
		if($meta_id <= 0)
		{
			return false;
		}

		$meta_value = maybe_serialize($meta->value);

		// hook
		do_action('wc1c_wc_data_storage_category_meta_update', $meta_id, $data->getId(), $meta->key, $meta_value);

		$result = update_term_meta($meta_id, $meta->key, $meta_value);

		if(!$result)
		{
			return false;
		}

		// hook
		do_action('wc1c_wc_data_storage_category_meta_updated', $meta->meta_id, $data->getId(), $meta->key, $meta_value);

		return true;
	}
}
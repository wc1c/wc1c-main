<?php namespace Wc1c\Wc\Storages;

defined('ABSPATH') || exit;

use stdClass;
use WP_Query;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Wc\Contracts\ImageContract;
use Wc1c\Wc\Contracts\ImagesStorageContract;
use Wc1c\Wc\Contracts\MetaStorageContract;
use Wc1c\Wc\Contracts\StorageContract;
use Wc1c\Wc\Entities\Image;

/**
 * ImagesStorage
 *
 * @package Wc1c\Wc
 */
class ImagesStorage implements ImagesStorageContract, StorageContract, MetaStorageContract
{
	/**
	 * Method to create a new object in the database
	 *
	 * @param ImageContract $data Data object
	 */
	public function create(&$data)
	{
		$attachment =
		[
			'post_mime_type' => $data->getMimeType(),
			'guid' => '',
			'post_parent' => $data->getProductId(),
			'post_title' => $data->getName(),
			'post_content' => $data->getDescription(),
			'post_author' => $data->getUserId()
		];

		$object_id = wp_insert_attachment($attachment, $data->getName(), $data->getProductId());

		if(!is_wp_error($object_id))
		{
			wp_update_attachment_metadata($object_id, wp_generate_attachment_metadata($object_id, ''));
		}

		if($object_id && !is_wp_error($object_id))
		{
			$data->setId($object_id);

			$data->saveMetaData();
			$data->applyChanges();

			// hook
			do_action('wc1c_wc_data_storage_image_create', $object_id, $data);
		}
	}

	/**
	 * Method to read an object from the database
	 *
	 * @param ImageContract $data Data object
	 *
	 * @throws Exception If invalid category
	 */
	public function read(&$data)
	{
		$data->setDefaults();

		if(!$data->getId())
		{
			throw new Exception(__('Invalid image.', 'wc1c'));
		}

		$post_object = get_post($data->getId());

		if(!is_wp_error($post_object))
		{
			$data->setProps
			(
				[
					'name' => $post_object->post_title,
					'description'=> $post_object->post_content,
					'parent_id' => $post_object->post_parent,
					'slug' => $post_object->post_name,
					'guid' => $post_object->guid,
					'mime_type' => $post_object->post_mime_type,
					'user_id' => $post_object->post_author
				]
			);
		}

		$data->setObjectRead(true);

		do_action('wc1c_wc_data_storage_image_read', $data->getId());
	}

	/**
	 * Method to update a data in the database
	 *
	 * @param ImageContract $data Data object
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
					'parent_id',
					'mime_type',
					'user_id',
				],
				array_keys($changes)
			)
		)
		{
			$args =
			[
				'ID' => $data->getId(),
				'post_title' => $data->getName('edit'),
				'post_content' => $data->getDescription('edit'),
				'post_parent' => $data->getParentId('edit'),
				'post_author' => $data->getUserId('edit'),
				'post_mime_type' => $data->getMimeType('edit'),
				'post_type' => 'attachment'
			];

			wp_update_post($args);

			$data->readMetaData();
		}

		$data->applyChanges();

		do_action('wc1c_wc_data_storage_image_update', $data->getId(), $data);
	}

	/**
	 * Method to delete an object from the database
	 *
	 * @param ImageContract $data Data object
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
			do_action('wc1c_wc_data_storage_image_before_delete', $object_id);

			wp_delete_attachment($object_id, $args['force_delete']);

			$data->setId(0);

			do_action('wc1c_wc_data_storage_image_after_delete', $object_id);
		}
		else
		{
			do_action('wc1c_wc_data_storage_image_before_trash', $object_id);

			wp_delete_attachment($object_id, false);

			do_action('wc1c_wc_data_storage_image_after_trash', $object_id);
		}
	}

	/**
	 * Returns an array of meta for an object.
	 *
	 * @param ImageContract $data Data object
	 *
	 * @return array
	 */
	public function readMeta(&$data): array
	{
		$raw_meta_data = get_post_meta($data->getId());

		return apply_filters('wc1c_wc_data_storage_image_meta_read', $raw_meta_data, $data, $this);
	}

	/**
	 * Deletes meta based on meta ID
	 *
	 * @param ImageContract $data Data object
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
		do_action('wc1c_wc_data_storage_image_meta_delete', [$meta_id, $data->getId(), $meta->key, $meta->value]);

		$result = delete_post_meta($data->getId(), $meta->key, $meta->value);

		// hook
		do_action('wc1c_wc_data_storage_image_meta_deleted', [$meta_id, $data->getId(), $meta->key, $meta->value]);

		return $result;
	}

	/**
	 * Add new piece of meta
	 *
	 * @param ImageContract $data Data object
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
		do_action('wc1c_wc_data_storage_image_meta_add', $data->getId(), $meta_key, $_meta_value);

		$result = add_post_meta($data->getId(), $meta_key, $meta_value, false);

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
		do_action('wc1c_wc_data_storage_image_meta_added', $meta_id, $data->getId(), $meta_key, $_meta_value);

		return $meta_id;
	}

	/**
	 * Update meta
	 *
	 * @param ImageContract $data Data object
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
		do_action('wc1c_wc_data_storage_image_meta_update', $meta_id, $data->getId(), $meta->key, $meta_value);

		$result = update_post_meta($data->getId(), $meta->key, $meta_value);

		if(!$result)
		{
			return false;
		}

		// hook
		do_action('wc1c_wc_data_storage_image_meta_updated', $meta->meta_id, $data->getId(), $meta->key, $meta_value);

		return true;
	}

	/**
	 * Получение изображения или изображений по наименованию изображения из WooCommerce
	 *
	 * @param string $name Наименование искомой категории
	 *
	 * @return false|ImageContract
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
			'posts_per_page' => 1,
			'post_status' => 'inherit',
			'post_type' => 'attachment',
			'name' => trim($name),
		];

		$attachments = new WP_Query($args);

		if(isset($attachments->posts[0]))
		{
			return new Image($attachments->posts[0]->ID);
		}

		return false;
	}

	/**
	 * Получение изображения или изображений по наименованию изображения в 1С
	 *
	 * @param string $name Наименование изображения
	 *
	 * @return false|ImageContract
	 * @throws Exception
	 */
	public function getByExternalName($name)
	{
		if(empty($name))
		{
			return false;
		}

		$args =
		[
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'posts_per_page' => 1,
			'meta_query' =>
			[
				[
					'key' => '_wc1c_external_name',
					'value' => $name,
				]
			]
		];

		$attachments = new WP_Query($args);

		if(isset($attachments->posts[0]))
		{
			return new Image($attachments->posts[0]->ID);
		}

		return false;
	}

	/**
	 * Загрузка изображения из внешнего источника
	 *
	 * @param string $path Путь до изображения
	 * @param ImageContract $image Добавляемое изображение
	 *
	 * @return false|int
	 */
	public function uploadByPath($path, $image)
	{
		if(!function_exists('wp_generate_attachment_metadata'))
		{
			include_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$new_file_name = basename($path);
		$image_content = file_get_contents($path);

		$upload = wp_upload_bits($new_file_name, null, $image_content);

		if($upload['error'])
		{
			return false;
		}

		$info = wp_check_filetype($upload['file']);
		$title = '';
		$content = '';

		$image_meta = wp_read_image_metadata($upload['file']);
		if($image_meta)
		{
			if(trim($image_meta['title'] ) && !is_numeric(sanitize_title($image_meta['title'])))
			{
				$title = wc_clean($image_meta['title']);
			}
			if(trim( $image_meta['caption'] ) )
			{
				$content = wc_clean($image_meta['caption']);
			}
		}

		$attachment =
		[
			'post_mime_type' => $info['type'],
			'guid' => $upload['url'],
			'post_parent' => $image->getProductId(),
			'post_title' => $image->getName() ?: $title,
			'post_content' => $image->getDescription() ?: $content,
			'post_author' => $image->getUserId()
		];

		$attachment_id = wp_insert_attachment($attachment, $upload['file'], $image->getProductId());

		if(!is_wp_error($attachment_id))
		{
			$image->setId($attachment_id);
			$image->save();

			wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $upload['file']));

			return $attachment_id;
		}

		return false;
	}
}
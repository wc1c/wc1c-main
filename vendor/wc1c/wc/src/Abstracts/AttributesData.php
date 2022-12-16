<?php namespace Wc1c\Wc\Abstracts;

defined('ABSPATH') || exit;

use WP_Error;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Wc\Meta;
use Wc1c\Wc\Storage;

/**
 * AttributesData
 *
 * @package Wc1c\Wc
 */
abstract class AttributesData extends DataAbstract
{
	/**
	 * @var string This is the name of this object type
	 */
	protected $object_type = 'attribute';

	/**
	 * @var array|null
	 */
	protected $meta_data = null;

	/**
	 * AttributesData constructor.
	 *
	 * @param int $data
	 *
	 * @throws Exception
	 */
	public function __construct($data = 0)
	{
		parent::__construct();

		if(is_numeric($data) && $data > 0)
		{
			$this->setId($data);
		}
		elseif($data instanceof self)
		{
			$this->setId(absint($data->getId()));
		}
		else
		{
			$this->setObjectRead(true);
		}

		$this->storage = Storage::load($this->object_type);

		if($this->getId() > 0)
		{
			$this->storage->read($this);
		}
	}

	/**
	 * Filter null meta values from array
	 *
	 * @param mixed $meta Meta value to check
	 *
	 * @return bool
	 */
	protected function filterNullMeta($meta)
	{
		return !is_null($meta->value);
	}

	/**
	 * Get all meta data for object
	 *
	 * @return array of objects
	 */
	public function getMetaData()
	{
		$this->maybeReadMetaData();

		return array_values(array_filter($this->meta_data, [$this, 'filterNullMeta']));
	}

	/**
	 * Returns all data for this object
	 *
	 * @return array
	 */
	public function getData()
	{
		return array_merge(['id' => $this->getId()], $this->data, ['meta_data' => $this->getMetaData()]);
	}

	/**
	 * Set a collection of props in one go, collect any errors, and return the result
	 *
	 * Only sets using public methods
	 *
	 * @param array $props Key value pairs to set. Key is the prop and should map to a setter function name
	 * @param string $context In what context to run this
	 *
	 * @return bool|WP_Error
	 */
	public function setProps($props, $context = 'set')
	{
		$errors = false;

		foreach($props as $prop => $value)
		{
			try
			{
				if(is_null($value) || in_array($prop, ['prop', 'date_prop', 'meta_data'], true))
				{
					continue;
				}

				$prop = str_replace(' ', '', ucwords(str_replace('_', ' ', $prop)));

				$setter = "set$prop";

				if(is_callable([$this, $setter]))
				{
					$this->{$setter}($value);
				}
			}
			catch(Exception $e)
			{
				if(!$errors)
				{
					$errors = new WP_Error();
				}

				$errors->add($e->getCode(), $e->getMessage());
			}
		}

		return $errors && count($errors->get_error_codes()) ? $errors : true;
	}

	/**
	 * Get Metadata by Key
	 *
	 * @param string $key Meta Key
	 * @param bool $single return first found meta with key, or all with $key
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return mixed
	 */
	public function getMeta($key = '', $single = true, $context = 'view')
	{
		$this->maybeReadMetaData();

		$meta_data = $this->getMetaData();
		$array_keys = array_keys(wp_list_pluck($meta_data, 'key'), $key, true);
		$value = $single ? '' : [];

		if(!empty($array_keys))
		{
			if($single)
			{
				$value = $meta_data[current($array_keys)]->value;
			}
			else
			{
				$value = array_intersect_key($meta_data, array_flip($array_keys));
			}
		}

		if('view' === $context)
		{
			$value = apply_filters($this->getHookPrefix() . $key, $value, $this);
		}

		return $value;
	}

	/**
	 * See if metadata exists, since get_meta always returns a '' or array()
	 *
	 * @param string $key meta Key
	 *
	 * @return boolean
	 */
	public function metaExists($key = '')
	{
		$this->maybeReadMetaData();

		$array_keys = wp_list_pluck($this->getMetaData(), 'key');

		return in_array($key, $array_keys, true);
	}

	/**
	 * Set all metadata from array
	 *
	 * @param array $data Key/Value pairs
	 */
	public function setMetaData($data)
	{
		if(!empty($data) && is_array($data))
		{
			$this->maybeReadMetaData();

			foreach($data as $meta)
			{
				$meta = (array) $meta;
				if(isset($meta['key'], $meta['value'], $meta['id']))
				{
					$this->meta_data[] = new Meta
					(
						[
							'id' => $meta['id'],
							'key' => $meta['key'],
							'value' => $meta['value'],
						]
					);
				}
			}
		}
	}

	/**
	 * Add metadata
	 *
	 * @param string $key Meta key
	 * @param string|array $value Meta value
	 * @param bool $unique Should this be a unique key?
	 *
	 * @return void
	 */
	public function addMetaData($key, $value, $unique = false)
	{
		$this->maybeReadMetaData();

		if($unique)
		{
			$this->deleteMetaData($key);
		}

		$this->meta_data[] = new Meta
		(
			[
				'key' => $key,
				'value' => $value,
			]
		);
	}

	/**
	 * Update meta data by key or ID, if provided
	 *
	 * @param string $key Meta key
	 * @param string|array $value Meta value
	 * @param int $meta_id Meta ID
	 *
	 * @return void
	 */
	public function updateMetaData($key, $value, $meta_id = 0)
	{
		$this->maybeReadMetaData();

		$array_key = false;

		if($meta_id)
		{
			$array_keys = array_keys(wp_list_pluck($this->meta_data, 'id'), $meta_id, true);
			$array_key = $array_keys ? current($array_keys) : false;
		}
		else
		{
			$matches = [];
			foreach($this->meta_data as $meta_data_array_key => $meta)
			{
				if($meta->key === $key)
				{
					$matches[] = $meta_data_array_key;
				}
			}

			if(!empty($matches))
			{
				foreach($matches as $meta_data_array_key)
				{
					$this->meta_data[$meta_data_array_key]->value = null;
				}
				$array_key = current($matches);
			}
		}

		if(false !== $array_key)
		{
			$meta = $this->meta_data[$array_key];
			$meta->key = $key;
			$meta->value = $value;
		}
		else
		{
			$this->addMetaData($key, $value, true);
		}
	}

	/**
	 * Delete metadata
	 *
	 * @param string $key Meta key
	 */
	public function deleteMetaData($key)
	{
		$this->maybeReadMetaData();

		$array_keys = array_keys(wp_list_pluck($this->meta_data, 'key'), $key, true);

		if($array_keys)
		{
			foreach($array_keys as $array_key)
			{
				$this->meta_data[$array_key]->value = null;
			}
		}
	}

	/**
	 * Delete metadata by ID
	 *
	 * @param int $mid Meta ID
	 */
	public function deleteMetaDataById($mid)
	{
		$this->maybeReadMetaData();
		$array_keys = array_keys(wp_list_pluck($this->meta_data, 'id'), (int) $mid, true);

		if($array_keys)
		{
			foreach($array_keys as $array_key)
			{
				$this->meta_data[$array_key]->value = null;
			}
		}
	}

	/**
	 * Read meta data if null
	 */
	protected function maybeReadMetaData()
	{
		if(is_null($this->meta_data))
		{
			$this->readMetaData();
		}
	}

	/**
	 * Read Metadata from the database. Ignore any internal properties
	 *
	 * Uses its own caches because get_metadata does not provide meta_ids
	 */
	public function readMetaData()
	{
		$this->meta_data = [];

		if(!$this->getId())
		{
			return;
		}

		if(!$this->storage)
		{
			return;
		}

		$raw_meta_data = $this->storage->readMeta($this);

		if($raw_meta_data)
		{
			foreach($raw_meta_data as $meta_key => $meta_value)
			{
				$this->meta_data[] = new Meta
				(
					[
						'id' => (int)$this->getId(),
						'key' => $meta_key,
						'value' => maybe_unserialize($meta_value),
					]
				);
			}
		}
	}

	/**
	 * Update Metadata in the database
	 */
	public function saveMetaData()
	{
		if(!$this->storage || is_null($this->meta_data))
		{
			return;
		}

		foreach($this->meta_data as $array_key => $meta)
		{
			if(is_null($meta->value))
			{
				if(!empty($meta->id))
				{
					$this->storage->deleteMeta($this, $meta);
					unset($this->meta_data[$array_key]);
				}
			}
			elseif(empty($meta->id))
			{
				$meta->id = $this->storage->addMeta($this, $meta);
				$meta->applyChanges();
			}
			else if($meta->getChanges())
			{
				$this->storage->updateMeta($this, $meta);
				$meta->applyChanges();
			}
		}
	}
}
<?php namespace Digiom\Woplucore\Data\Abstracts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Data\Meta;
use Digiom\Woplucore\Data\Exceptions\Exception;
use WP_Error;

/**
 * WithMetaDataAbstract - Implemented by classes using the same CRUD(s) pattern with metadata
 *
 * @package Digiom\Woplucore\Data\Abstracts
 */
abstract class WithMetaDataAbstract extends DataAbstract
{
	/**
	 * @var array|null
	 */
	protected $meta_data = null;

	/**
	 * Filter null meta values from array
	 *
	 * @param mixed $meta Meta value to check
	 *
	 * @return bool
	 */
	protected function filterNullMeta($meta): bool
	{
		return !is_null($meta->value);
	}

	/**
	 * Get all meta data for object
	 *
	 * @return array of objects
	 */
	public function getMetaData(): array
	{
		$this->maybeReadMetaData();

		return array_values(array_filter($this->meta_data, [$this, 'filterNullMeta']));
	}

	/**
	 * Returns all data for this object
	 *
	 * @return array
	 */
	public function getData(): array
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
	public function setProps(array $props, string $context = 'set')
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
	 * Check if the key is an internal one
	 *
	 * @param string $key key to check
	 *
	 * @return bool true if it's an internal key, false otherwise
	 */
	protected function isInternalMetaKey(string $key): bool
	{
		$internal_meta_key = !empty($key) && $this->storage && in_array($key, $this->storage->getInternalMetaKeys(), true);

		if(!$internal_meta_key)
		{
			return false;
		}

		$has_setter_or_getter = is_callable([$this, 'set_' . $key]) || is_callable([$this, 'get_' . $key]);

		if(!$has_setter_or_getter)
		{
			return false;
		}

		return true;
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
	public function getMeta(string $key = '', bool $single = true, string $context = 'view')
	{
		if($this->isInternalMetaKey($key))
		{
			$function = 'get_' . $key;

			if(is_callable([$this, $function]))
			{
				return $this->{$function}();
			}
		}

		$this->maybeReadMetaData();

		$meta_data = $this->getMetaData();
		$array_keys = array_keys(wp_list_pluck($meta_data, 'key'), $key, true);
		$value = $single ? '' : [];

		if(!empty($array_keys))
		{
			// We don't use the $this->meta_data property directly here because we don't want meta with a null value (i.e. meta which has been deleted via $this->delete_meta_data()).
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
	public function metaExists(string $key = ''): bool
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
	public function setMetaData(array $data)
	{
		if(!empty($data))
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
	public function addMetaData(string $key, $value, bool $unique = false)
	{
		if($this->isInternalMetaKey($key))
		{
			$function = 'set_' . $key;

			if(is_callable([$this, $function]))
			{
				return $this->{$function}($value);
			}
		}

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
	 * @return mixed|void
	 */
	public function updateMetaData(string $key, $value, int $meta_id = 0)
	{
		if($this->isInternalMetaKey($key))
		{
			$function = 'set_' . $key;

			if(is_callable([$this, $function]))
			{
				return $this->{$function}($value);
			}
		}

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
	public function deleteMetaData(string $key)
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
	public function deleteMetaDataById(int $mid)
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
			foreach($raw_meta_data as $meta)
			{
				$this->meta_data[] = new Meta
				(
					[
						'id' => (int) $meta->meta_id,
						'key' => $meta->name,
						'value' => maybe_unserialize($meta->value),
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
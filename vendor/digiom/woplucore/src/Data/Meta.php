<?php namespace Digiom\Woplucore\Data;

defined('ABSPATH') || exit;

/**
 * Meta
 *
 * @package Digiom\Woplucore\Data
 */
class Meta implements \JsonSerializable
{
	/**
	 * @var array Metadata data
	 */
	protected $data;

	/**
	 * Current data for metadata
	 *
	 * @var array
	 */
	protected $current_data;

	/**
	 * Constructor
	 *
	 * @param array $meta Data to wrap behind this function
	 */
	public function __construct(array $meta = [])
	{
		$this->current_data = $meta;

		$this->applyChanges();
	}

	/**
	 * When converted to JSON
	 *
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return $this->getData();
	}

	/**
	 * Merge changes with data and clear.
	 */
	public function applyChanges()
	{
		$this->data = $this->current_data;
	}

	/**
	 * Creates or updates a property in the metadata object
	 *
	 * @param string $key Key to set
	 * @param mixed $value Value to set
	 */
	public function __set(string $key, $value)
	{
		$this->current_data[$key] = $value;
	}

	/**
	 * Checks if a given key exists in our data. This is called internally
	 * by `empty` and `isset`
	 *
	 * @param string $key Key to check if set
	 *
	 * @return bool
	 */
	public function __isset(string $key)
	{
		return array_key_exists($key, $this->current_data);
	}

	/**
	 * Returns the value of any property
	 *
	 * @param string $key Key to get
	 *
	 * @return mixed Property value or NULL if it does not exists
	 */
	public function __get(string $key)
	{
		if(array_key_exists($key, $this->current_data))
		{
			return $this->current_data[$key];
		}

		return null;
	}

	/**
	 * Return data changes only
	 *
	 * @return array
	 */
	public function getChanges(): array
	{
		$changes = [];
		foreach($this->current_data as $id => $value)
		{
			if(!array_key_exists($id, $this->data) || $value !== $this->data[$id])
			{
				$changes[$id] = $value;
			}
		}

		return $changes;
	}

	/**
	 * Return all data as an array
	 *
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}
}

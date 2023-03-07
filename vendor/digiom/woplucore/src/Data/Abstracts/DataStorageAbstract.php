<?php namespace Digiom\Woplucore\Data\Abstracts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Data\Exceptions\Exception;
use Digiom\Woplucore\Data\Interfaces\DataStorageInterface;

/**
 * DataStorageAbstract
 *
 * @package Wc1c\Main\Data\Storages
 */
abstract class DataStorageAbstract implements DataStorageInterface
{
	/**
	 * @return string
	 */
	abstract public function getTableName(): string;

	/**
	 * Method to create a new object in the database
	 *
	 * @param DataAbstract $data Data object
	 *
	 * @throws Exception
	 */
	abstract public function create(&$data);

	/**
	 * Method to read an object from the database
	 *
	 * @param DataAbstract $data Data object
	 *
	 * @throws Exception If invalid configuration
	 */
	abstract public function read(&$data);

	/**
	 * Method to update a data in the database
	 *
	 * @param DataAbstract $data Data object
	 */
	abstract public function update(&$data);

	/**
	 * Method to delete an object from the database
	 *
	 * @param DataAbstract $data Data object
	 * @param array $args Array of args to pass to the delete method
	 */
	abstract public function delete(&$data, array $args = []): bool;

	/**
	 * Check if id is found for any other objects IDs
	 *
	 * @param int $object_id ID
	 *
	 * @return bool
	 */
	abstract public function isExistingById(int $object_id): bool;

	/**
	 * Read extra data associated with the object, like button text or code URL for external objects.
	 *
	 * @param DataAbstract $data Data object
	 */
	protected function readExtraData(&$data)
	{
		foreach($data->getExtraDataKeys() as $extra_data_key)
		{
			$function = 'set_' . $extra_data_key;
			if(is_callable([$data, $function]))
			{
				$data->{$function}(
					get_post_meta($data->getId(), '_' . $extra_data_key, true) // todo get_post_meta
				);
			}
		}
	}

	/**
	 * Returns an array of data
	 *
	 * @param array $args Args
	 * @param mixed $type
	 *
	 * @return mixed
	 */
	abstract public function getData(array $args = [], $type = OBJECT);
}
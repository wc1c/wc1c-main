<?php namespace Wc1c\Main\Data\Contracts;

defined('ABSPATH') || exit;

use Wc1c\Main\Data\Abstracts\DataAbstract;

/**
 * StorageContract
 *
 * @package Wc1c\Main\Data\Contracts
 */
interface StorageContract
{
	/**
	 * Method to create a new record of a Data based object
	 *
	 * @param DataAbstract $data Data object
	 */
	public function create(&$data);

	/**
	 * Method to read a record.
	 *
	 * @param DataAbstract $data Data object
	 */
	public function read(&$data);

	/**
	 * Updates a record in the database
	 *
	 * @param DataAbstract $data Data object
	 */
	public function update(&$data);

	/**
	 * Deletes a record from the database
	 *
	 * @param DataAbstract $data Data object
	 * @param array $args Array of args to pass to the delete method
	 *
	 * @return bool result
	 */
	public function delete(&$data, $args = []);
}
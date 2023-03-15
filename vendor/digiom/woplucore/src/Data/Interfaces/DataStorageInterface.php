<?php namespace Digiom\Woplucore\Data\Interfaces;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Data\Abstracts\DataAbstract;

/**
 * DataStorageInterface
 *
 * @package Digiom\Woplucore\Data\Interfaces
 */
interface DataStorageInterface
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
	public function delete(&$data, array $args = []): bool;
}
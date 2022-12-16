<?php namespace Wc1c\Main\Data\Contracts;

defined('ABSPATH') || exit;

use Wc1c\Main\Data\Abstracts\DataAbstract;

/**
 * MetaStorageContract
 *
 * @package Wc1c\Main\Data\Contracts
 */
interface MetaStorageContract
{
	/**
	 * Returns an array of meta for an object
	 *
	 * @param DataAbstract $data Data object
	 *
	 * @return array
	 */
	public function readMeta(&$data);

	/**
	 * Deletes meta based on meta ID
	 *
	 * @param DataAbstract $data Data object
	 * @param object $meta Meta an object (containing at least ->id)
	 *
	 * @return array
	 */
	public function deleteMeta(&$data, $meta);

	/**
	 * Add new piece of meta.
	 *
	 * @param DataAbstract $data Data object
	 * @param object $meta Meta object (containing ->key and ->value)
	 *
	 * @return int meta ID
	 */
	public function addMeta(&$data, $meta);

	/**
	 * Update meta
	 *
	 * @param DataAbstract $data Data object
	 * @param object $meta Meta object (containing ->id, ->key and ->value)
	 */
	public function updateMeta(&$data, $meta);
}
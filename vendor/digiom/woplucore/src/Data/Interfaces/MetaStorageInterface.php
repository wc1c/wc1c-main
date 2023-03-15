<?php namespace Digiom\Woplucore\Data\Interfaces;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Data\Abstracts\DataAbstract;
use Digiom\Woplucore\Data\Meta;

/**
 * MetaStorageInterface
 *
 * @package Digiom\Woplucore\Data\Interfaces
 */
interface MetaStorageInterface
{
	/**
	 * Returns an array of meta for an object
	 *
	 * @param DataAbstract $data Data object
	 *
	 * @return array
	 */
	public function readMeta(&$data): array;

	/**
	 * Deletes meta based on meta ID
	 *
	 * @param DataAbstract $data Data object
	 * @param Meta $meta Meta an object (containing at least ->id)
	 *
	 * @return array
	 */
	public function deleteMeta(&$data, Meta $meta);

	/**
	 * Add new piece of meta.
	 *
	 * @param DataAbstract $data Data object
	 * @param Meta $meta Meta object (containing ->key and ->value)
	 *
	 * @return int meta ID
	 */
	public function addMeta(&$data, Meta $meta): int;

	/**
	 * Update meta
	 *
	 * @param DataAbstract $data Data object
	 * @param Meta $meta Meta object (containing ->id, ->key and ->value)
	 */
	public function updateMeta(&$data, Meta $meta);
}
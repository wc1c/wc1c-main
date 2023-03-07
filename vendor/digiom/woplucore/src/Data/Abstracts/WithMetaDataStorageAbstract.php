<?php namespace Digiom\Woplucore\Data\Abstracts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Data\Interfaces\MetaStorageInterface;
use Digiom\Woplucore\Data\Meta;
use Digiom\Woplucore\Traits\DatetimeUtilityTrait;

/**
 * DataStorageAbstract
 *
 * @package Wc1c\Main\Data\Storages
 */
abstract class WithMetaDataStorageAbstract extends DataStorageAbstract implements MetaStorageInterface
{
	use DatetimeUtilityTrait;

	/**
	 * @var array Data stored in meta keys, but not considered "meta" for an object.
	 */
	protected $internal_meta_keys = [];

	/**
	 * @var array Metadata which should exist in the DB, even if empty
	 */
	protected $must_exist_meta_keys = [];

	/**
	 * @return string
	 */
	public function getMetaTableName(): string
	{
		return $this->getTableName() . '_meta';
	}

	/**
	 * Return list of internal meta keys
	 *
	 * @return array
	 */
	public function getInternalMetaKeys(): array
	{
		return $this->internal_meta_keys;
	}

	/**
	 * Callback to remove unwanted meta data
	 *
	 * @param Meta $meta Meta object to check if it should be excluded or not
	 *
	 * @return bool
	 */
	protected function excludeInternalMetaKeys(Meta $meta): bool
	{
		return !in_array($meta->meta_key, $this->internal_meta_keys, true) && 0 !== stripos($meta->meta_key, 'wp_');
	}

	/**
	 * Add new piece of meta
	 *
	 * @param DataAbstract $data Data object
	 * @param Meta $meta (containing ->key and ->value)
	 *
	 * @return int meta ID
	 */
	abstract public function addMeta(&$data, Meta $meta): int;

	/**
	 * Deletes meta based on meta ID
	 *
	 * @param DataAbstract $data Data object
	 * @param Meta $meta (containing at least -> id).
	 */
	abstract public function deleteMeta(&$data, Meta $meta);

	/**
	 * Update meta
	 *
	 * @param DataAbstract $data Data object
	 * @param Meta $meta (containing ->id, ->key and ->value).
	 */
	abstract public function updateMeta(&$data, Meta $meta);

	/**
	 * Get meta data by meta ID
	 *
	 * @param int $meta_id ID for a specific meta row
	 *
	 * @return object|false Meta object or false.
	 */
	abstract public function getMetadataById(int $meta_id);

	/**
	 * Returns an array of meta for an object.
	 *
	 * @param DataAbstract $data Data object
	 *
	 * @return array
	 */
	abstract public function readMeta(&$data): array;

	/**
	 * Internal meta keys we don't want exposed as part of meta_data. This is in
	 * addition to all data props with _ prefix.
	 *
	 * @param string $key Prefix to be added to meta keys
	 *
	 * @return string
	 */
	protected function prefixKey(string $key): string
	{
		return 0 === strpos($key, '_') ? $key : '_' . $key;
	}
}
<?php namespace Wc1c\Cml;

defined('ABSPATH') || exit;

/**
 * Utility
 *
 * @package Wc1c\Cml
 */
trait Utility
{
	/**
	 * Determining the file type
	 *
	 * @param string $file
	 *
	 * @return string|false
	 */
	private function cmlDetectFileType(string $file)
	{
		$types =
		[
			'import',
			'offers',
			'prices',
			'rests',
			'import_files',
			'goods',
			'units',
			'storages',
			'priceLists',
			'propertiesGoods',
			'groups'
		];

		foreach($types as $type)
		{
			$pos = stripos($file, $type);
			if($pos !== false)
			{
				return $type;
			}
		}

		return false;
	}
}
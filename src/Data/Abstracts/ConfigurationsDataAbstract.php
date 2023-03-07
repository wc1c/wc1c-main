<?php namespace Wc1c\Main\Data\Abstracts;

defined('ABSPATH') || exit;

/**
 * ConfigurationsDataAbstract
 *
 * @package Wc1c\Main\Data\Abstracts
 */
abstract class ConfigurationsDataAbstract extends WithMetaDataAbstract
{
	/**
	 * This is the name of this object type
	 *
	 * @var string
	 */
	protected $object_type = 'configuration';
}
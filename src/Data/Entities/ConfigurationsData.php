<?php namespace Wc1c\Main\Data\Entities;

defined('ABSPATH') || exit;

use Wc1c\Main\Data\Abstracts\WithMetaDataAbstract;

/**
 * ConfigurationsData
 *
 * @package Wc1c\Main\Data\Entities
 */
abstract class ConfigurationsData extends WithMetaDataAbstract
{
	/**
	 * This is the name of this object type
	 *
	 * @var string
	 */
	protected $object_type = 'configuration';
}
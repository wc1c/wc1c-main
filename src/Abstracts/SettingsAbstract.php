<?php namespace Wc1c\Main\Abstracts;

defined('ABSPATH') || exit;

/**
 * SettingsAbstract
 *
 * @package Wc1c\Main\Abstracts
 */
abstract class SettingsAbstract extends \Digiom\Woplucore\Abstracts\SettingsAbstract
{
	/**
	 * @var string Name option prefix in wp_options
	 */
	protected $option_name_prefix = 'wc1c_settings_';
}
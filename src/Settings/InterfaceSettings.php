<?php namespace Wc1c\Main\Settings;

defined('ABSPATH') || exit;

use Wc1c\Main\Settings\Abstracts\SettingsAbstract;

/**
 * InterfaceSettings
 *
 * @package Wc1c\Main\Settings
 */
class InterfaceSettings extends SettingsAbstract
{
	/**
	 * InterfaceSettings constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('interface');
	}
}
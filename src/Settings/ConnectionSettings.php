<?php namespace Wc1c\Main\Settings;

defined('ABSPATH') || exit;

use Wc1c\Main\Abstracts\SettingsAbstract;

/**
 * ConnectionSettings
 *
 * @package Wc1c\Main\Settings
 */
class ConnectionSettings extends SettingsAbstract
{
	/**
	 * ConnectionSettings constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('connection');
	}
}
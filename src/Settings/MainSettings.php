<?php namespace Wc1c\Main\Settings;

defined('ABSPATH') || exit;

use Wc1c\Main\Settings\Abstracts\SettingsAbstract;

/**
 * Class MainSettings
 *
 * @package Wc1c\Main\Settings
 */
class MainSettings extends SettingsAbstract
{
	/**
	 * Main constructor.
	 */
	public function __construct()
	{
		$this->setOptionName('main');
	}
}
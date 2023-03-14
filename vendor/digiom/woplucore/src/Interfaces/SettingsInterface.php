<?php namespace Digiom\Woplucore\Interfaces;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Exceptions\Exception;

/**
 * SettingsInterface
 *
 * @package Digiom\Woplucore\Interfaces
 */
interface SettingsInterface
{
	/**
	 * Initializing
	 *
	 * @return void
	 * @throws Exception
	 */
	public function init();

	/**
	 * Get - all or single
	 *
	 * @param string $setting_key - optional
	 * @param mixed $default_return - default data, optional
	 *
	 * @return bool|string|array
	 */
	public function get(string $setting_key = '', $default_return = '');

	/**
	 * Set - all or single
	 *
	 * @param mixed $setting_data - all data, or single
	 * @param string $setting_key - optional
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function set($setting_data = '', string $setting_key = '');

	/**
	 * Save
	 *
	 * @param mixed $settings_data Data to save
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function save($settings_data = null): bool;
}
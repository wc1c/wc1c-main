<?php namespace Digiom\Woplucore\Abstracts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Interfaces\SettingsInterface;
use Digiom\Woplucore\Exceptions\Exception;
use Digiom\Woplucore\Exceptions\RuntimeException;

/**
 * SettingsAbstract
 *
 * @package Digiom\Woplucore\Abstracts
 */
abstract class SettingsAbstract implements SettingsInterface
{
	/**
	 * @var string Name option prefix in wp_options
	 */
	protected $option_name_prefix = 'plugin_settings_';

	/**
	 * @var string Name option in wp_options
	 */
	protected $option_name = '';

	/**
	 * @var array Current data
	 */
	private $data = [];

	/**
	 * Set option name with prefix
	 *
	 * @param string $name Option name without prefix
	 */
	public function setOptionName(string $name)
	{
		$this->option_name = $this->option_name_prefix . $name;
	}

	/**
	 * @return string
	 */
	public function getOptionName(): string
	{
		return $this->option_name;
	}

	/**
	 * Initializing
	 *
	 * @return void
	 * @throws RuntimeException
	 */
	public function init()
	{
		// get data from wp_options
		$settings = get_site_option($this->getOptionName() , []);

		// hook
		$settings = apply_filters($this->getOptionName() . 'data_init', $settings);

		if(!is_array($settings))
		{
			throw new RuntimeException('init: $settings is not array');
		}

		$settings = array_merge
		(
			$this->getData(),
			$settings
		);

		try
		{
			$this->setData($settings);
		}
		catch(\Throwable $e)
		{
			throw new RuntimeException('init: exception - ' . $e->getMessage());
		}
	}

	/**
	 * Set setting data - single or all
	 *
	 * @param string|array $setting_data
	 * @param string $setting_key
	 *
	 * @return boolean
	 * @throws RuntimeException
	 */
	public function set($setting_data = '', string $setting_key = ''): bool
	{
		if(empty($setting_key) && !is_array($setting_data))
		{
			return false;
		}

		$current_data = $this->getData();

		if(is_array($setting_data) && empty($setting_key))
		{
			$current_data = array_merge
			(
				$current_data,
				$setting_data
			);
		}
		else
		{
			$current_data[$setting_key] = $setting_data;
		}

		try
		{
			$this->setData($current_data);
		}
		catch(\Throwable $e)
		{
			throw new RuntimeException('set: exception - ' . $e->getMessage());
		}

		return true;
	}

	/**
	 * Save
	 *
	 * @param $settings_data array|null - optional
	 *
	 * @return bool
	 * @throws RuntimeException
	 */
	public function save($settings_data = null): bool
	{
		$current_data = $this->getData();

		if(is_array($settings_data))
		{
			$settings_data = array_merge($current_data, $settings_data);
		}
		else
		{
			$settings_data = $current_data;
		}

		$settings_data = apply_filters($this->getOptionName() . 'data_save', $settings_data);

		try
		{
			$this->setData($settings_data);
		}
		catch(\Throwable $e)
		{
			throw new RuntimeException('save: exception - ' . $e->getMessage());
		}

		/**
		 * Update in DB
		 */
		return update_option($this->getOptionName(), $settings_data, 'no');
	}

	/**
	 * Get settings - all or single
	 *
	 * @param string $setting_key - optional
	 * @param string $default_return - default data, optional
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function get(string $setting_key = '', $default_return = '')
	{
		try
		{
			$data = $this->getData();
		}
		catch(Exception $e)
		{
			throw new RuntimeException('get: exception - ' . $e->getMessage());
		}

		if('' !== $setting_key)
		{
			if(array_key_exists($setting_key, $data))
			{
				return $data[$setting_key];
			}

			return $default_return;
		}

		return $data;
	}

	/**
	 * Get all data
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	private function getData(): array
	{
		if(!is_array($this->data))
		{
			throw new RuntimeException('get_data: $data is not valid array');
		}

		return $this->data;
	}

	/**
	 * Set all data
	 *
	 * @param array $data
	 *
	 * @return void
	 * @throws RuntimeException
	 */
	private function setData(array $data = [])
	{
		if(!is_array($data))
		{
			throw new RuntimeException('set_data: $data is not valid');
		}

		$this->data = $data;
	}
}
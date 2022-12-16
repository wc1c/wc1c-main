<?php namespace Wc1c\Main\Extensions\Abstracts;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Exceptions\RuntimeException;
use Wc1c\Main\Extensions\Contracts\ExtensionContract;

/**
 * ExtensionAbstract
 *
 * @package Wc1c\Main\Extensions
 */
abstract class ExtensionAbstract implements ExtensionContract
{
	/**
	 * @var string Unique extension id
	 */
	private $id = '';

	/**
	 * @var array Extension meta
	 */
	public $meta = [];

	/**
	 * @var bool Extension initialized flag
	 */
	private $initialized = false;

	/**
	 * ExtensionAbstract constructor.
	 */
	public function __construct(){}

	/**
	 * Initializing extension
	 *
	 * @return boolean Initialize or no
	 * @throws Exception
	 */
	abstract public function init();

	/**
	 * @return boolean
	 */
	public function isInitialized()
	{
		return $this->initialized;
	}

	/**
	 * @param boolean $initialized
	 */
	public function setInitialized($initialized)
	{
		$this->initialized = $initialized;
	}

	/**
	 * Set extension id
	 *
	 * @param string|integer $id Extension id
	 *
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Get extension id
	 *
	 * @return string Extension id
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set meta information for extension
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setMeta($name, $value = '')
	{
		$this->meta[$name] = $value;

		return $this;
	}

	/**
	 * Get meta information for extension
	 *
	 * @param $name
	 * @param string $default_value
	 *
	 * @return mixed|string
	 * @throws RuntimeException
	 */
	public function getMeta($name, $default_value = '')
	{
		$data = $this->meta;

		if('' !== $name)
		{
			if(is_array($data) && array_key_exists($name, $data))
			{
				return $data[$name];
			}

			return $default_value;
		}

		throw new RuntimeException(__('Meta value by name is not available.', 'wc1c-main'));
	}

	/**
	 * Load meta data by plugin file
	 *
	 * @param string $file
	 * @param string $locale
	 *
	 * @return boolean
	 */
	public function loadMetaByPlugin($file, $locale = '')
	{
		if(!function_exists('get_file_data'))
		{
			return false;
		}

		$default_headers =
		[
			'Name' => 'Plugin Name',
			'PluginURI' => 'Plugin URI',
			'Version' => 'Version',
			'Description' => 'Description',
			'Author' => 'Author',
			'AuthorURI' => 'Author URI',
			'TextDomain' => 'Text Domain',
			'DomainPath' => 'Domain Path',
			'Network' => 'Network',
			'RequiresWP' => 'Requires at least',
			'RequiresPHP' => 'Requires PHP',
			'RequiresWC' => 'WC requires at least',
			'TestedWC' => 'WC tested up to',
			'RequiresWC1C' => 'Requires WC1C',
			'TestedWC1C' => 'WC1C tested up to',
		];

		$plugin_data = get_file_data($file, $default_headers, 'plugin');

		if(!isset($plugin_data['Version']))
		{
			return false;
		}

		if('' === $locale)
		{
			$locale = $plugin_data['TextDomain'];
		}

		$this->setMeta('version', $plugin_data['Version']);
		$this->setMeta('version_php_min', $plugin_data['RequiresPHP']);
		$this->setMeta('version_wp_min', $plugin_data['RequiresWP']);

		$this->setMeta('version_wc_min', $plugin_data['RequiresWC']);
		$this->setMeta('version_wc_max', $plugin_data['TestedWC']);

		$this->setMeta('version_wc1c_min', $plugin_data['RequiresWC1C']);
		$this->setMeta('version_wc1c_max', $plugin_data['TestedWC1C']);

		$this->setMeta('author', __($plugin_data['Author'], $locale));
		$this->setMeta('name', __($plugin_data['Name'], $locale));
		$this->setMeta('description', __($plugin_data['Description'], $locale));

		return true;
	}
}
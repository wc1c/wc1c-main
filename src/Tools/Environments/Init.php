<?php namespace Wc1c\Main\Tools\Environments;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Tools\Abstracts\ToolAbstract;
use Wc1c\Main\Traits\UtilityTrait;

/**
 * Init
 *
 * @package Wc1c\Main\Tools\Environments
 */
class Init extends ToolAbstract
{
	use UtilityTrait;

	/**
	 * @var array Wc1c\Main data
	 */
	private $wc1c_data = [];

	/**
	 * @var array Server data
	 */
	private $server_data = [];

	/**
	 * @var array WordPress data
	 */
	private $wp_data = [];

	/**
	 * @var array WooCommerce data
	 */
	private $wc_data = [];

	/**
	 * Init constructor.
	 */
	public function __construct()
	{
		$this->init();
	}

	/**
	 * Initialize
	 */
	public function init()
	{
		$this->setId('environments');
		$this->setName(__('Environments', 'wc1c-main'));
		$this->setDescription(__('Data about all current environments.', 'wc1c-main'));

		if(!$this->utilityIsWc1cAdminToolsRequest('environments'))
		{
			return;
		}

		add_action('wc1c_admin_tools_single_show', [$this, 'output']);

		/**
		 * Print
		 */
		add_filter('wc1c_admin_report_data_row_print', [$this, 'filter_data_row_print'], 10, 2);

		/**
		 * WC1C data output
		 */
		add_action('wc1c_admin_tools_single_show', [$this, 'wc1c_data_output'], 10);

		/**
		 * WC data output
		 */
		add_action('wc1c_admin_tools_single_show', [$this, 'wc_data_output'], 10);

		/**
		 * WP data output
		 */
		add_action('wc1c_admin_tools_single_show', [$this, 'wp_data_output'], 10);

		/**
		 * Server data output
		 */
		add_action('wc1c_admin_tools_single_show', [$this, 'server_data_output'], 10);
	}

	/**
	 * Show on page
	 */
	public function output()
	{
		//echo 'Example content';
	}

	/**
	 * Normalize data to print
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function filter_data_row_print($data)
	{
		/**
		 * Boolean
		 */
		if(is_bool($data))
		{
			if($data)
			{
				$data = __('yes', 'wc1c-main');
			}
			else
			{
				$data = __('not', 'wc1c-main');
			}
		}

		/**
		 * Array
		 */
		if(is_array($data))
		{
			$data = implode(', ', $data);
		}

		return $data;
	}

	/**
	 * WordPress data output
	 *
	 * @return void
	 */
	public function wp_data_output()
	{
		$wp_data = $this->load_wp_data();

		$args = ['title' => __('WordPress environment', 'wc1c-main'), 'data' => $wp_data];

		wc1c()->views()->getView('tools/environments/item.php', $args);
	}

	/**
	 * WC1C data output
	 *
	 * @return void
	 */
	public function wc1c_data_output()
	{
		$wc1c_data = $this->load_wc1c_data();

		$args = ['title' => __('WC1C environment', 'wc1c-main'), 'data' => $wc1c_data];

		wc1c()->views()->getView('tools/environments/item.php', $args);
	}

	/**
	 * WooCommerce data output
	 *
	 * @return void
	 */
	public function wc_data_output()
	{
		$wp_data = $this->load_wc_data();

		$args = ['title' => __('WooCommerce environment', 'wc1c-main'), 'data' => $wp_data];

		wc1c()->views()->getView('tools/environments/item.php', $args);
	}

	/**
	 * Server data output
	 *
	 * @return void
	 */
	public function server_data_output()
	{
		$server_data = $this->load_server_data();

		$args = ['title' => __('Server environment', 'wc1c-main'), 'data' => $server_data];

		wc1c()->views()->getView('tools/environments/item.php', $args);
	}

	/**
	 * WordPress data
	 *
	 * @return array
	 */
	public function load_wp_data()
	{
		/**
		 * Final
		 *
		 * title: show title, required
		 * description: optional
		 * data: raw data for entity
		 */
		$env_array = [];

		/**
		 * Home URL
		 */
		$env_array['wp_home_url'] = array
		(
			'title' => __('Home URL', 'wc1c-main'),
			'description' => '',
			'data' => get_option('home')
		);

		/**
		 * Site URL
		 */
		$env_array['wp_site_url'] = array
		(
			'title' => __('Site URL', 'wc1c-main'),
			'description' => '',
			'data' => get_option('siteurl')
		);

		/**
		 * Version
		 */
		$env_array['wp_version'] = array
		(
			'title' => __('WordPress version', 'wc1c-main'),
			'description' => '',
			'data' => get_bloginfo('version')
		);

		/**
		 * WordPress multisite
		 */
		$env_array['wp_multisite'] = array
		(
			'title' => __('WordPress multisite', 'wc1c-main'),
			'description' => '',
			'data' => is_multisite()
		);

		/**
		 * WordPress debug
		 */
		$env_array['wp_debug_mode'] = array
		(
			'title' => __('WordPress debug', 'wc1c-main'),
			'description' => '',
			'data' => (defined( 'WP_DEBUG' ) && WP_DEBUG)
		);

		/**
		 * WordPress debug
		 */
		$env_array['wp_cron'] = array
		(
			'title' => __('WordPress cron', 'wc1c-main'),
			'description' => '',
			'data' => !(defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON)
		);

		/**
		 * WordPress language
		 */
		$env_array['wp_language'] = array
		(
			'title' => __('WordPress language', 'wc1c-main'),
			'description' => '',
			'data' => get_locale()
		);

		/**
		 * WordPress memory limit
		 */
		$env_array['wp_memory_limit'] = array
		(
			'title' => __('WordPress memory limit', 'wc1c-main'),
			'description' => '',
			'data' => WP_MEMORY_LIMIT
		);

		/**
		 * Set wp data
		 */
		$this->set_wp_data($env_array);

		/**
		 * Return wp data
		 */
		return $this->get_wp_data();
	}

	/**
	 * Server data
	 */
	public function load_server_data()
	{
		/**
		 * Final
		 *
		 * title: show title, required
		 * description: optional
		 * data: raw data for entity
		 */
		$env_array = [];

		/**
		 * Server info
		 */
		$env_array['server_info'] = array
		(
			'title' => __('Server info', 'wc1c-main'),
			'description' => '',
			'data' => $_SERVER['SERVER_SOFTWARE']
		);

		/**
		 * PHP version
		 */
		$env_array['php_version'] = array
		(
			'title' => __('PHP version', 'wc1c-main'),
			'description' => '',
			'data' => PHP_VERSION
		);

		/**
		 * Database version
		 */
		$env_array['db_version'] = array
		(
			'title' => __('Database version', 'wc1c-main'),
			'description' => '',
			'data' => (!empty(wc1c()->database()->is_mysql) ? wc1c()->database()->db_version() : '')
		);

		/**
		 * Suhosin
		 */
		$env_array['suhosin_installed'] = array
		(
			'title' => __('Suhosin', 'wc1c-main'),
			'description' => '',
			'data' => extension_loaded('suhosin')
		);

		/**
		 * Fsockopen or curl enabled
		 */
		$env_array['fsockopen_or_curl'] = array
		(
			'title' => __('Fsockopen or curl enabled', 'wc1c-main'),
			'description' => '',
			'data' => (function_exists('fsockopen') || function_exists('curl_init'))
		);

		/**
		 * CURL
		 */
		if(function_exists('curl_version'))
		{
			$curl_version = curl_version();

			$env_array['curl_version'] = array
			(
				'title' => __('CURL info', 'wc1c-main'),
				'description' => '',
				'data' => $curl_version['version'] . ', ' . $curl_version['ssl_version']
			);
		}

		/**
		 * Default timezone
		 */
		$env_array['default_timezone'] = array
		(
			'title' => __('Default timezone', 'wc1c-main'),
			'description' => '',
			'data' => date_default_timezone_get()
		);

		/**
		 * PHP post max size
		 */
		$env_array['php_post_max_size'] = array
		(
			'title' => __('PHP post max size', 'wc1c-main'),
			'description' => '',
			'data' => ini_get('post_max_size')
		);

		/**
		 * PHP max upload size
		 */
		$env_array['php_max_upload_size'] = array
		(
			'title' => __('PHP max upload size', 'wc1c-main'),
			'description' => '',
			'data' => (wp_max_upload_size() / 1024 / 1024) . 'M'
		);

		/**
		 * PHP max execution time
		 */
		$env_array['php_max_execution_time'] = array
		(
			'title' => __('PHP max execution time', 'wc1c-main'),
			'description' => '',
			'data' => ini_get('max_execution_time')
		);

		/**
		 * PHP max input vars
		 */
		$env_array['php_max_input_vars'] = array
		(
			'title' => __('PHP max input vars', 'wc1c-main'),
			'description' => '',
			'data' => ini_get('max_input_vars')
		);

		/**
		 * PHP soapclient enabled
		 */
		$env_array['php_soapclient_enabled'] = array
		(
			'title' => __('PHP soapclient enabled', 'wc1c-main'),
			'description' => '',
			'data' => class_exists('SoapClient')
		);

		/**
		 * PHP domdocument enabled
		 */
		$env_array['php_domdocument_enabled'] = array
		(
			'title' => __('PHP domdocument enabled', 'wc1c-main'),
			'description' => '',
			'data' => class_exists('DOMDocument')
		);

		/**
		 * PHP gzip enabled
		 */
		$env_array['php_gzip_enabled'] = array
		(
			'title' => __('PHP gzip enabled', 'wc1c-main'),
			'description' => '',
			'data' => is_callable('gzopen')
		);

		/**
		 * PHP mbstring enabled
		 */
		$env_array['php_mbstring_enabled'] = array
		(
			'title' => __('PHP mbstring enabled', 'wc1c-main'),
			'description' => '',
			'data' => extension_loaded('mbstring')
		);

		/**
		 * Set server data
		 */
		$this->set_server_data($env_array);

		/**
		 * Return final server data
		 */
		return $this->get_server_data();
	}

	/**
	 * WC1C data
	 */
	public function load_wc1c_data()
	{
		/**
		 * Container
		 */
		$env_array = [];

		/**
		 * WC1C version
		 */
		$env_array['wc1c_version'] = array
		(
			'title' => __('WC1C version', 'wc1c-main'),
			'description' => '',
			'data' => WC1C()->environment()->get('wc1c_version', '')
		);

		/**
		 * WC1C upload directory
		 */
		$env_array['wc1c_upload_directory'] = array
		(
			'title' => __('Upload directory', 'wc1c-main'),
			'description' => '',
			'data' => WC1C()->environment()->get('wc1c_upload_directory')
		);

		/**
		 * Extensions count
		 */
		try
		{
			$extensions = wc1c()->extensions()->get();
			$env_array['wc1c_extensions_count'] = array
			(
				'title' => __('Count extensions', 'wc1c-main'),
				'description' => '',
				'data' => count($extensions)
			);
		}
		catch(Exception $e){}

		/**
		 * Schemas count
		 */
		try
		{
			$schemas = wc1c()->schemas()->get();
			$env_array['wc1c_schemas_count'] = array
			(
				'title' => __('Count schemas', 'wc1c-main'),
				'description' => '',
				'data' => count($schemas)
			);
		}
		catch(Exception $e)
		{}

		/**
		 * Tools count
		 */
		try
		{
			$tools = wc1c()->tools()->get();
			$env_array['wc1c_tools_count'] = array
			(
				'title' => __('Count tools', 'wc1c-main'),
				'description' => '',
				'data' => count($tools)
			);
		}
		catch(Exception $e)
		{}

		$this->set_wc1c_data($env_array);

		return $this->get_wc1c_data();
	}

	/**
	 * WooCommerce data
	 */
	private function load_wc_data()
	{
		/**
		 * Container
		 */
		$env_array = [];

		/**
		 * WooCommerce version
		 */
		$env_array['wc_version'] = array
		(
			'title' => __('WooCommerce version', 'wc1c-main'),
			'description' => '',
			'data' => WC()->version
		);

		$term_response = [];
		$terms = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
		foreach($terms as $term)
		{
			$term_response[$term->slug] = strtolower($term->name);
		}

		/**
		 * Product types
		 */
		$env_array['wc_product_types'] = array
		(
			'title' => __('WooCommerce product types', 'wc1c-main'),
			'description' => '',
			'data' => $term_response
		);

		/**
		 * WooCommerce currency
		 */
		$env_array['wc_currency'] = array
		(
			'title' => __('WooCommerce currency', 'wc1c-main'),
			'description' => '',
			'data' => get_woocommerce_currency()
		);

		/**
		 * WooCommerce currency symbol
		 */
		$env_array['wc_currency_symbol'] = array
		(
			'title' => __('WooCommerce currency symbol', 'wc1c-main'),
			'description' => '',
			'data' => get_woocommerce_currency_symbol()
		);

		/**
		 * Final set
		 */
		$this->set_wc_data($env_array);

		/**
		 * Return all data
		 */
		return $this->get_wc_data();
	}

	/**
	 * Get WooCommerce data
	 *
	 * @return array
	 */
	public function get_wc_data()
	{
		return $this->wc_data;
	}

	/**
	 * Set WooCommerce data
	 *
	 * @param array $wc_data
	 */
	public function set_wc_data($wc_data)
	{
		$this->wc_data = $wc_data;
	}

	/**
	 * @return array
	 */
	public function get_wc1c_data()
	{
		return $this->wc1c_data;
	}

	/**
	 * @param array $wc1c_data
	 */
	public function set_wc1c_data($wc1c_data)
	{
		$this->wc1c_data = $wc1c_data;
	}

	/**
	 * @return array
	 */
	public function get_server_data()
	{
		return $this->server_data;
	}

	/**
	 * @param array $server_data
	 */
	public function set_server_data($server_data)
	{
		$this->server_data = $server_data;
	}

	/**
	 * @return array
	 */
	public function get_wp_data()
	{
		return $this->wp_data;
	}

	/**
	 * @param array $wp_data
	 */
	public function set_wp_data($wp_data)
	{
		$this->wp_data = $wp_data;
	}
}
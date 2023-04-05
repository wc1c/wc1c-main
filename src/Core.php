<?php namespace Wc1c\Main;

defined('ABSPATH') || exit;

use Wc1c\Main\Abstracts\SettingsAbstract;
use Wc1c\Main\Log\StreamHandler;
use wpdb;
use Digiom\Woplucore\Abstracts\CoreAbstract;
use Digiom\Woplucore\Traits\SingletonTrait;
use Psr\Log\LoggerInterface;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Log\Formatter;
use Wc1c\Main\Log\Handler;
use Wc1c\Main\Log\Logger;
use Wc1c\Main\Log\Processor;
use Wc1c\Main\Settings\ConnectionSettings;
use Wc1c\Main\Settings\InterfaceSettings;
use Wc1c\Main\Settings\LogsSettings;
use Wc1c\Main\Settings\MainSettings;

/**
 * Core
 *
 * @package Wc1c\Main
 */
final class Core extends CoreAbstract
{
	use SingletonTrait;

	/**
	 * @var array
	 */
	private $log = [];

	/**
	 * @var Timer
	 */
	private $timer;

	/**
	 * @var SettingsAbstract[]
	 */
	private $settings = [];

	/**
	 * @var Receiver
	 */
	private $receiver;

	/**
	 * @var Tecodes\Client
	 */
	private $tecodes;

	/**
	 * Core constructor.
	 *
	 * @return void
	 */
	public function __construct()
	{
		do_action('wc1c_core_loaded');
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		// hook
		do_action('wc1c_before_init');

		$this->localization();

		try
		{
			$this->timer();
		}
		catch(Exception $e)
		{
			wc1c()->log()->alert(__('Timer not loaded.', 'wc1c-main'), ['exception' => $e]);
			return;
		}

		try
		{
			$this->extensions()->load();
		}
		catch(Exception $e)
		{
			wc1c()->log()->alert(__('Extensions not loaded.', 'wc1c-main'), ['exception' => $e]);
		}

		try
		{
			$this->extensions()->init();
		}
		catch(Exception $e)
		{
			wc1c()->log()->alert(__('Extensions not initialized.', 'wc1c-main'), ['exception' => $e]);
		}

		try
		{
			$this->schemas()->load();
		}
		catch(Exception $e)
		{
			wc1c()->log()->alert(__('Schemas not loaded.', 'wc1c-main'), ['exception' => $e]);
		}

		try
		{
			$this->tools()->load();
		}
		catch(Exception $e)
		{
			wc1c()->log()->alert(__('Tools not loaded.', 'wc1c-main'), ['exception' => $e]);
		}

		if(false !== wc1c()->context()->isReceiver() || false !== wc1c()->context()->isAdmin())
		{
			try
			{
				$this->tools()->init();
			}
			catch(Exception $e)
			{
				wc1c()->log()->alert(__('Tools not initialized.', 'wc1c-main'), ['exception' => $e]);
			}
		}

		if(false !== wc1c()->context()->isReceiver())
		{
			try
			{
				$this->loadReceiver();
			}
			catch(Exception $e)
			{
				wc1c()->log()->alert(__('Receiver not loaded.', 'wc1c-main'), ['exception' => $e]);
			}
		}

		// hook
		do_action('wc1c_after_init');
	}

	/**
	 * Extensions
	 *
	 * @return Extensions\Core
	 */
	public function extensions(): Extensions\Core
	{
		return Extensions\Core::instance();
	}

	/**
	 * Filesystem
	 *
	 * @return Filesystem
	 */
	public function filesystem(): Filesystem
	{
		return Filesystem::instance();
	}

	/**
	 * Schemas
	 *
	 * @return Schemas\Core
	 */
	public function schemas(): Schemas\Core
	{
		return Schemas\Core::instance();
	}

	/**
	 * Environment
	 *
	 * @return Environment
	 */
	public function environment(): Environment
	{
		return Environment::instance();
	}

	/**
	 * Views
	 *
	 * @return Views
	 */
	public function views(): Views
	{
		return Views::instance()->setSlug('wc1c-main')->setPluginDir($this->environment()->get('plugin_directory_path'));
	}

	/**
	 * Tools
	 *
	 * @return Tools\Core
	 */
	public function tools(): Tools\Core
	{
		return Tools\Core::instance();
	}

	/**
	 * Logger
	 *
	 * @param string $channel
	 * @param string $name
	 * @param mixed $hard_level
	 *
	 * @return LoggerInterface
	 */
	public function log(string $channel = 'main', string $name = '', $hard_level = null)
	{
		$channel = strtolower($channel);

		if(!isset($this->log[$channel]))
		{
			if('' === $name)
			{
				$name = $channel;
			}

			$path = '';
			$max_files = $this->settings('logs')->get('logger_files_max', 30);

			$logger = new Logger($channel);

			switch($channel)
			{
				case 'receiver':
					$level = $this->settings('logs')->get('logger_receiver_level', 'logger_level');
					break;
				case 'tools':
					$path = $this->environment()->get('wc1c_tools_logs_directory') . '/' . $name . '.log';
					$level = $this->settings('logs')->get('logger_tools_level', 'logger_level');
					break;
				case 'schemas':
					$path = $this->environment()->get('wc1c_schemas_logs_directory') . '/' . $name . '.log';
					$level = $this->settings('logs')->get('logger_schemas_level', 'logger_level');
					break;
				case 'configurations':
					$path = $name . '.log';
					$level = $this->settings('logs')->get('logger_configurations_level', 'logger_level');
					break;
				default:
					$level = $this->settings('logs')->get('logger_level', 300);
			}

			if('logger_level' === $level)
			{
				$level = $this->settings('logs')->get('logger_level', 300);
			}

			if(!is_null($hard_level))
			{
				$level = $hard_level;
			}

			if('' === $path)
			{
				$path = $this->environment()->get('wc1c_logs_directory') . '/main.log';
			}

			try
			{
				$uid_processor = new Processor();
				$formatter = new Formatter();
				$handler = new Handler($path, $max_files, $level);

				$handler->setFormatter($formatter);

				$logger->pushProcessor($uid_processor);
				$logger->pushHandler($handler);

				if('yes' === $this->settings('logs')->get('logger_output', 'no'))
				{
					$logger->pushHandler(new StreamHandler('php://output', Logger::DEBUG));
				}
			}
			catch(\Throwable $e){}

			/**
			 * Внешние назначения для логгера
			 *
			 * @param LoggerInterface $logger Текущий логгер
			 *
			 * @return LoggerInterface
			 */
			if(has_filter('wc1c_log_load_before'))
			{
				$logger = apply_filters('wc1c_log_load_before', $logger);
			}

			$this->log[$channel] = $logger;
		}

		return $this->log[$channel];
	}

	/**
	 * Settings
	 *
	 * @param string $context
	 *
	 * @return SettingsAbstract
	 */
	public function settings(string $context = 'main')
	{
		if(!isset($this->settings[$context]))
		{
			switch($context)
			{
				case 'connection':
					$class = ConnectionSettings::class;
					break;
				case 'logs':
					$class = LogsSettings::class;
					break;
				case 'interface':
					$class = InterfaceSettings::class;
					break;
				default:
					$class = MainSettings::class;
			}

			$settings = new $class();

			try
			{
				$settings->init();
			}
			catch(Exception $e)
			{
				wc1c()->log()->error($e->getMessage(), ['exception' => $e]);
			}

			$this->settings[$context] = $settings;
		}

		return $this->settings[$context];
	}

	/**
	 * Timer
	 *
	 * @return Timer
	 */
	public function timer(): Timer
	{
		if(is_null($this->timer))
		{
			$timer = new Timer();

			$php_max_execution = $this->environment()->get('php_max_execution_time', 20);

			if($php_max_execution !== $this->settings()->get('php_max_execution_time', $php_max_execution))
			{
				$php_max_execution = $this->settings()->get('php_max_execution_time', $php_max_execution);
			}

			$timer->setMaximum($php_max_execution);

			$this->timer = $timer;
		}

		return $this->timer;
	}

	/**
	 * Tecodes
	 *
	 * @return Tecodes\Client
	 */
	public function tecodes(): Tecodes\Client
	{
		if($this->tecodes instanceof Tecodes\Client)
		{
			return $this->tecodes;
		}

		if(!class_exists('Tecodes_Local'))
		{
			include_once $this->environment()->get('plugin_directory_path') . '/vendor/tecodes/tecodes-local/bootstrap.php';
		}

		$options =
		[
			'timeout' => 5,
			'verify_ssl' => false,
			'version' => 'tecodes/v1'
		];

		$tecodes_local = new Tecodes\Client('https://wc1c.info/', $options);

		/**
		 * Languages
		 */
		$tecodes_local->status_messages =
		[
			'status_1' => __('This activation code is active.', 'wc1c-main'),
			'status_2' => __('Error: This activation code has expired.', 'wc1c-main'),
			'status_3' => __('Activation code republished. Awaiting reactivation.', 'wc1c-main'),
			'status_4' => __('Error: This activation code has been suspended.', 'wc1c-main'),
			'code_not_found' => __('This activation code is not found.', 'wc1c-main'),
			'localhost' => __('This activation code is active (localhost).', 'wc1c-main'),
			'pending' => __('Error: This activation code is pending review.', 'wc1c-main'),
			'download_access_expired' => __('Error: This version of the software was released after your download access expired. Please downgrade software or contact support for more information.', 'wc1c-main'),
			'missing_activation_key' => __('Error: The activation code variable is empty.', 'wc1c-main'),
			'could_not_obtain_local_code' => __('Error: I could not obtain a new local code.', 'wc1c-main'),
			'maximum_delay_period_expired' => __('Error: The maximum local code delay period has expired.', 'wc1c-main'),
			'local_code_tampering' => __('Error: The local key has been tampered with or is invalid.', 'wc1c-main'),
			'local_code_invalid_for_location' => __('Error: The local code is invalid for this location.', 'wc1c-main'),
			'missing_license_file' => __('Error: Please create the following file (and directories if they dont exist already): ', 'wc1c-main'),
			'license_file_not_writable' => __('Error: Please make the following path writable: ', 'wc1c-main'),
			'invalid_local_key_storage' => __('Error: I could not determine the local key storage on clear.', 'wc1c-main'),
			'could_not_save_local_key' => __('Error: I could not save the local key.', 'wc1c-main'),
			'code_string_mismatch' => __('Error: The local code is invalid for this activation code.', 'wc1c-main'),
			'code_status_delete' => __('Error: This activation code has been deleted.', 'wc1c-main'),
			'code_status_draft' => __('Error: This activation code has draft.', 'wc1c-main'),
			'code_status_available' => __('Error: This activation code has available.', 'wc1c-main'),
			'code_status_blocked' => __('Error: This activation code has been blocked.', 'wc1c-main'),
		];

		$tecodes_local->set_local_code_storage(new Tecodes\Storage());
		$tecodes_local->set_instance(new Tecodes\Instance());

		$tecodes_local->validate();

		$this->tecodes = $tecodes_local;

		return $this->tecodes;
	}

	/**
	 * Get Receiver
	 *
	 * @return Receiver
	 */
	public function receiver(): Receiver
	{
		return $this->receiver;
	}

	/**
	 * Set Receiver
	 *
	 * @param Receiver $receiver
	 */
	public function setReceiver(Receiver $receiver)
	{
		$this->receiver = $receiver;
	}

	/**
	 * Receiver loading
	 *
	 * @return void
	 * @throws Exception
	 */
	private function loadReceiver()
	{
		$default_class_name = Receiver::class;

		$use_class_name = apply_filters('wc1c_receiver_loading_class_name', $default_class_name);

		if(false === class_exists($use_class_name))
		{
			wc1c()->log()->error(__('Receiver loading: class is not exists, use is default.', 'wc1c-main'), ['context' => $use_class_name]);
			$use_class_name = $default_class_name;
		}

		$receiver = new $use_class_name();

		$receiver->register();

		$this->setReceiver($receiver);
	}

	/**
	 * Load localisation
	 */
	public function localization()
	{
		$locale = determine_locale();

		if(has_filter('plugin_locale'))
		{
			$locale = apply_filters('plugin_locale', $locale, 'wc1c-main');
		}

		load_textdomain('wc1c-main', WP_LANG_DIR . '/plugins/wc1c-main-' . $locale . '.mo');
		load_textdomain('wc1c-main', wc1c()->environment()->get('plugin_directory_path') . 'assets/languages/wc1c-main-' . $locale . '.mo');

		wc1c()->log()->debug(__('Localization loaded.', 'wc1c-main'), ['locale' => $locale]);
	}

	/**
	 * Use in plugin for DB queries
	 *
	 * @return wpdb
	 */
	public function database(): wpdb
	{
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Main instance of Admin
	 *
	 * @return Admin
	 */
	public function admin(): Admin
	{
		return Admin::instance();
	}

	/**
	 * Get data if set, otherwise return a default value or null
	 * Prevents notices when data is not set
	 *
	 * @param mixed $var variable
	 * @param string $default default value
	 *
	 * @return mixed
	 */
	public function getVar(&$var, $default = null)
	{
		return isset($var) ? $var : $default;
	}

	/**
	 * Define constant if not already set
	 *
	 * @param string $name constant name
	 * @param string|bool $value constant value
	 */
	public function define(string $name, $value)
	{
		if(!defined($name))
		{
			define($name, $value);
		}
	}
}
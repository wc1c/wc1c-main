<?php
/**
 * Default schema class
 *
 * @package Wc1c/Schemas
 */
defined('ABSPATH') || exit;

class Wc1c_Schema_Default extends Wc1c_Abstract_Schema
{
	/**
	 * Wc1c_Schema_Logger
	 *
	 * @var null
	 */
	private $logger = null;

	/**
	 * @var string
	 */
	private $import_full = true;

	/**
	 * Current time
	 *
	 * @var string
	 */
	private $time;

	/**
	 * Current import data
	 *
	 * @var array
	 */
	private $current_data = [];

	/**
	 * Main schema directory
	 *
	 * @var string
	 */
	private $upload_directory = '';

	/**
	 * Initialize
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function init()
	{
		/**
		 * Init environment
		 */
		$this->init_environment();

		/**
		 * Logger
		 */
		if(false === $this->load_logger())
		{
			WC1C()->logger()->critical('init: load_logger');
			throw new Exception('init: load_logger error');
		}

		$this->logger()->info('init: start');

		/**
		 * View configuration form
		 */
		if(true === is_wc1c_admin_request())
		{
			add_filter('wc1c_admin_configurations-update_form_load_fields', array($this, 'configurations_fields_auth'), 10, 1);
			add_filter('wc1c_admin_configurations-update_form_load_fields', array($this, 'configurations_fields_processing'), 10, 1);
			add_filter('wc1c_admin_configurations-update_form_load_fields', array($this, 'configurations_fields_tech'), 10, 1);
		}

		/**
		 * Api requests handler
		 */
		if(true === is_wc1c_api_request())
		{
			add_action('wc1c_api_' . $this->get_id(), array($this, 'api_handler'), 10);
		}

		$this->logger()->debug('init: end', $this);

		return true;
	}

	/**
	 * @return string
	 */
	public function get_upload_directory()
	{
		return $this->upload_directory;
	}

	/**
	 * @param string $upload_directory
	 */
	public function set_upload_directory($upload_directory)
	{
		$this->upload_directory = $upload_directory;
	}

	/**
	 * Schema environment
	 */
	private function init_environment()
	{
		$configuration_id = WC1C()->environment()->get('current_configuration_id', 0);

		$schema_directory = WC1C()->environment()->get('wc1c_upload_directory') . DIRECTORY_SEPARATOR . $this->get_id() . '_' . $configuration_id;

		$this->set_upload_directory($schema_directory);

		WC1C()->environment()->set('wc1c_current_schema_upload_directory', $this->get_upload_directory());
	}
	
	/**
	 * Load logger
	 */
	private function load_logger()
	{
		$path = $this->get_upload_directory();
		$level = $this->get_options('logger', 400);

		try
		{
			$logger = new Wc1c_Schema_Logger($path, $level, 'main.log');
		}
		catch(Exception $e)
		{
			return false;
		}

		try
		{
			$this->set_logger($logger);
		}
		catch(Exception $e)
		{
			return false;
		}

		return true;
	}

	/**
	 * Configuration fields: processing
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function configurations_fields_processing($fields)
	{
		$fields['title_processing'] = array
		(
			'title' => __('Processing details', 'wc1c'),
			'type' => 'title',
			'description' => __('Changing the behavior of the file processing.', 'wc1c'),
		);

		$fields['skip_file_processing'] = array
		(
			'title' => __('Skip processing of files', 'wc1c'),
			'type' => 'checkbox',
			'label' => __('Check the checkbox if want to enable this feature. Disabled by default.', 'wc1c'),
			'description' => __('Disabling the actual processing of CommerceML files. Files will be accepted, but instead of processing them, they will be skipped with successful completion of processing.', 'wc1c'),
			'default' => 'no'
		);

		$fields['delete_files_after_import'] = array
		(
			'title' => __('Deleting files after processing', 'wc1c'),
			'type' => 'checkbox',
			'label' => __('Check the checkbox if want to enable this feature. Disabled by default.', 'wc1c'),
			'description' => __('If deletion is disabled, the exchange files will remain in the directories until the next exchange. Otherwise, all processed files will be deleted immediately after error-free processing.', 'wc1c'),
			'default' => 'no'
		);

		return $fields;
	}

	/**
	 * Configuration fields: tech
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function configurations_fields_tech($fields)
	{
		$fields['title_tech'] = array
		(
			'title' => __('Technical details', 'wc1c'),
			'type' => 'title',
			'description' => __('Changing processing behavior for compatibility of the environment and other systems.', 'wc1c'),
		);

		$fields['logger'] = array
		(
			'title' => __('Logging level', 'wc1c'),
			'type' => 'select',
			'description' => __('You can enable logging, specify the level of error that you want to benefit from logging. You can send reports to developer manually by pressing the button. All sensitive data in the report are deleted. By default, the error rate should not be less than ERROR.', 'wc1c'),
			'default' => '400',
			'options' => array
			(
				'' => __('Off', 'wc1c'),
				'100' => __('DEBUG', 'wc1c'),
				'200' => __('INFO', 'wc1c'),
				'250' => __('NOTICE', 'wc1c'),
				'300' => __('WARNING', 'wc1c'),
				'400' => __('ERROR', 'wc1c'),
				'500' => __('CRITICAL', 'wc1c'),
				'550' => __('ALERT', 'wc1c'),
				'600' => __('EMERGENCY', 'wc1c')
			)
		);

		$fields['convert_cp1251'] = array
		(
			'title' => __('Converting to Windows-1251', 'wc1c'),
			'type' => 'checkbox',
			'label' => __('Check the checkbox if want to enable this feature. Disabled by default.', 'wc1c'),
			'description' => __('Data from utf-8 will be converted to Windows-1251 encoding. Use this feature for compatibility with older versions of 1C.', 'wc1c'),
			'default' => 'no'
		);

		$fields['post_file_max_size'] = array
		(
			'title' => __('Maximum request size', 'wc1c'),
			'type' => 'text',
			'description' => __('Enter the maximum request size. You can only reduce the value.', 'wc1c'),
			'default' => '',
			'css' => 'min-width: 100px;',
		);

		$fields['file_zip'] = array
		(
			'title' => __('Support for data compression', 'wc1c'),
			'type' => 'checkbox',
			'label' => __('Check the checkbox if want to enable this feature. Disabled by default.', 'wc1c'),
			'description' => __('1C can transfer files in archives to reduce the number of HTTP requests and compress data. In this case, the load may increase when unpacking archives, or even it may be impossible to unpack due to server restrictions.', 'wc1c'),
			'default' => 'no'
		);

		return $fields;
	}

	/**
	 * Configuration fields: auth
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function configurations_fields_auth($fields)
	{
		$fields['title_auth'] = array
		(
			'title' => __('Requests authorization', 'wc1c'),
			'type' => 'title',
			'description' => __('Data for authorization of requests. These settings will connect 1C.', 'wc1c'),
		);

		$url_raw = get_site_url(null, '/?wc1c-api=' . $this->configuration()->get_id() . '&get_param');
		$url_raw = '<p class="input-text p-2 bg-light regular-input wc1c_urls">' . $url_raw . '</p>';

		$fields['url_requests'] = array
		(
			'title' => __('Requests URL', 'wc1c'),
			'type' => 'raw',
			'raw' => $url_raw,
			'description' => __('This address is entered in the exchange settings on the 1C side. It will receive requests from 1C.', 'wc1c'),
		);

		$fields['user_login'] = array
		(
			'title' => __('Login to connect', 'wc1c'),
			'type' => 'text',
			'description' => __('Enter the username to connect from 1C. It should be the same as when setting up in 1C.', 'wc1c'),
			'default' => '',
			'css' => 'min-width: 350px;',
		);

		$fields['user_password'] = array
		(
			'title' => __('Password to connect', 'wc1c'),
			'type' => 'text',
			'description' => __('Enter the users password to connect from 1C. It must be the same as when setting up in 1C.', 'wc1c'),
			'default' => '',
			'css' => 'min-width: 350px;',
		);

		return $fields;
	}

	/**
	 * Get logger
	 *
	 * @return Wc1c_Schema_Logger|null
	 */
	protected function logger()
	{
		return $this->logger;
	}

	/**
	 * Set schema logger
	 *
	 * @param Wc1c_Schema_Logger|null $logger
	 */
	public function set_logger($logger)
	{
		$this->logger = $logger;
	}

	/**
	 * Возвращает максимальный объем файла в байтах для загрузки
	 *
	 * @return float|int
	 */
	private function get_post_file_size_max()
	{
		$size = wc1c_convert_size(ini_get('post_max_size'));

		$size_max_manual = wc1c_convert_size($this->get_options('post_file_max_size'));

		if($size_max_manual)
		{
			if($size_max_manual < $size)
			{
				$size = $size_max_manual;
			}
		}

		return $size;
	}

	/**
	 * Echo result
	 *
	 * @param string $type
	 * @param string $description
	 */
	private function api_response_by_type($type = 'failure', $description = '')
	{
		if($this->get_options('convert_cp1251', 'no') === 'yes' && $description !== '')
		{
			$description = mb_convert_encoding($description, 'cp1251', 'utf-8');
			header('Content-Type: text/html; charset=Windows-1251');
		}

		if($type == 'success')
		{
			echo "success\n";
		}
		else
		{
			echo "failure\n";
		}

		if($description != '')
		{
			echo $description;
		}
		exit;
	}

	/**
	 * Проверка авторизации
	 *
	 * @return bool
	 */
	private function api_check_auth_key()
	{
		$cookie_name = 'wc1c_' . $this->get_id();

		if(!isset($_COOKIE[$cookie_name]))
		{
			$this->logger()->warning('api_check_auth_key: $_COOKIE[$cookie_name] empty');
			return false;
		}

		$password = $this->get_options('user_password', '1234567890qwertyuiop');

		if($_COOKIE[$cookie_name] !== md5($password))
		{
			$this->logger()->warning('api_check_auth_key: $_COOKIE[$cookie_name] !== md5($password)');
			return false;
		}

		return true;
	}

	/**
	 * Api handler
	 */
	public function api_handler()
	{
		$mode = '';
		$type = '';

		if(wc1c_get_var($_GET['get_param'], '') !== '' || wc1c_get_var($_GET['get_param?type'], '') !== '')
		{
			$output = [];
			if(isset($_GET['get_param']))
			{
				$get_param = ltrim($_GET['get_param'], '?');
				parse_str($get_param, $output);
			}

			if(array_key_exists('mode', $output))
			{
				$mode = $output['mode'];
			}
			elseif(isset($_GET['mode']))
			{
				$mode = $_GET['mode'];
			}

			if(array_key_exists('type', $output))
			{
				$type = $output['type'];
			}
			elseif(isset($_GET['type']))
			{
				$type = $_GET['type'];
			}

			if($type == '')
			{
				$type = $_GET['get_param?type'];
			}
		}

		$this->logger()->info('api_handler: $type=' . $type . ' $mode=' . $mode);

		/**
		 * Catalog
		 */
		if($type === 'catalog' && $mode !== '')
		{
			switch($mode)
			{
				case 'checkauth':
					$this->api_check_auth();
					break;

				case 'init':
					$this->api_mode_init();
					break;

				case 'file':
					$this->api_catalog_mode_file();
					break;

				case 'import':
					$this->api_catalog_mode_import();
					break;

				default:
					$this->api_response_by_type('success');
			}
		}

		$this->api_response_by_type('success');
	}

	/**
	 * Checkauth
	 */
	private function api_check_auth()
	{
		$user_login = '';
		$user_password = '';

		if(!isset($_SERVER['PHP_AUTH_USER']))
		{
			if(isset($_SERVER["REMOTE_USER"]))
			{
				$remote_user = $_SERVER["REMOTE_USER"];

				if(isset($_SERVER["REDIRECT_REMOTE_USER"]))
				{
					$remote_user = $_SERVER["REMOTE_USER"] ? $_SERVER["REMOTE_USER"] : $_SERVER["REDIRECT_REMOTE_USER"];
				}
			}
			elseif(isset($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]))
			{
				$remote_user = $_SERVER["REDIRECT_HTTP_AUTHORIZATION"];
			}

			if(isset($remote_user))
			{
				$strTmp = base64_decode(substr($remote_user, 6));

				if($strTmp)
				{
					list($user_login, $user_password) = explode(':', $strTmp);
				}
			}
			else
			{
				$this->logger()->notice('Проверьте наличие записи в файле .htaccess в корне файла после RewriteEngine On:\nRewriteCond %{HTTP:Authorization} ^(.*)\nRewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]');
				$this->api_response_by_type('failure', __('Not specified the user. Check the server settings.', 'wc1c'));
			}
		}
		else
		{
			$user_login = $_SERVER['PHP_AUTH_USER'];
			$user_password = $_SERVER['PHP_AUTH_PW'];
		}

		if($user_login !== $this->get_options('user_login', ''))
		{
			$this->logger()->notice(__('Not a valid username', 'wc1c'));
			$this->api_response_by_type('failure', __('Not a valid username', 'wc1c'));
		}

		if($user_password !== $this->get_options('user_password', ''))
		{
			$this->logger()->notice(__('Not a valid user password', 'wc1c'));
			$this->api_response_by_type('failure', __('Not a valid user password', 'wc1c'));
		}

		if($user_password === '')
		{
			$user_password = '1234567890qwertyuiop';
		}

		echo "success\n";
		echo "wc1c_" . $this->get_id() . "\n";
		echo md5($user_password);
		exit;
	}

	/**
	 * Init
	 *
	 * При успешной инициализации возвращает временный файл с данными:
	 * в 1-ой строке содержится признак, разрешен ли Zip (zip=yes);
	 * во 2-ой строке содержится информация об ограничении файлов по размеру (file_limit=);
	 */
	private function api_mode_init()
	{
		if($this->api_check_auth_key() === false)
		{
			$this->api_response_by_type('failure', __('Authorization failed', 'wc1c'));
		}

		$zip_support = false;
		if(class_exists('ZipArchive'))
		{
			$this->logger()->info('api_mode_init: ZipArchive available');
			$zip_support = true;
		}

		$data[0] = 'zip=no';
		if($zip_support && $this->get_options('file_zip', 'no') === 'yes')
		{
			$data[0] = 'zip=yes';
		}

		$manual_size = wc1c_convert_size($this->get_options('post_file_max_size'));
		$post_max_size = $this->get_post_file_size_max();

		$data[1] = 'file_limit=' . $post_max_size;
		if($this->get_options('post_file_max_size') && $manual_size <= $post_max_size)
		{
			$data[1] = 'file_limit=' . $manual_size;
		}

		$this->logger()->debug('api_mode_init: $data', $data);

		echo $data[0] . "\n";
		echo $data[1] . "\n";
		exit;
	}

	/**
	 * Выгрузка файлов в локальный каталог
	 *
	 * @return void
	 */
	private function api_catalog_mode_file()
	{
		if($this->api_check_auth_key() === false)
		{
			$this->api_response_by_type('failure', __('Authorization failed', 'wc1c'));
		}

		$schema_upload_dir = $this->get_upload_directory() . '/catalog/';

		if(!is_dir($schema_upload_dir))
		{
			mkdir($schema_upload_dir, 0777, true);

			if(!is_dir($schema_upload_dir))
			{
				$this->api_response_by_type('failure', __('Unable to create a directory: ', 'wc1c') . $schema_upload_dir);
			}
		}

		/**
		 * Empty filename
		 */
		if(wc1c_get_var($_GET['filename'], '') === '')
		{
			$this->logger()->warning('api_catalog_mode_file: filename is empty');
			$this->api_response_by_type('failure', __('Filename is empty.', 'wc1c'));
		}

		$filename = wc1c_get_var($_GET['filename'], '');

		$schema_upload_file_path = $schema_upload_dir . $filename;

		$this->logger()->info('api_catalog_mode_file: $schema_upload_file_path - ' . $schema_upload_file_path);

		if(strpos($filename, 'import_files') !== false)
		{
			$this->logger()->info('api_catalog_mode_file: clean_upload_file_tree');
			$this->clean_upload_file_tree(dirname($filename), $schema_upload_dir);
		}

		if(!is_writable($schema_upload_dir))
		{
			$this->logger()->info('api_catalog_mode_file: directory - ' . $schema_upload_dir . " is not writable!");
			$this->api_response_by_type('failure', 'Невозможно записать файлы в: ' . $schema_upload_dir);
		}

		$file_data = file_get_contents('php://input');

		if($file_data !== false)
		{
			$file_size = file_put_contents($schema_upload_file_path, $file_data, LOCK_EX);

			if($file_size)
			{
				$this->logger()->info('api_catalog_mode_file: $file_size - ' . $file_size);

				@chmod($schema_upload_file_path , 0777);

				if(strpos($filename, '.zip') !== false)
				{
					$xml_files_result = $this->extract_zip($schema_upload_file_path);

					if($this->get_options('delete_zip_files_after_import', 'no') === 'yes')
					{
						$this->logger()->info('api_catalog_mode_file: file zip deleted - ' . $schema_upload_file_path);
						unlink($schema_upload_file_path);
					}

					if($xml_files_result === false)
					{
						$this->logger()->info('api_catalog_mode_file: error extract file - ' . $schema_upload_file_path);
						$this->api_response_by_type('failure');
					}

					$this->api_response_by_type('success', 'Архив успешно принят и распакован.');
				}

				$this->logger()->info('api_catalog_mode_file: upload file - ' . $schema_upload_file_path . ' success');
				$this->api_response_by_type('success', 'Файл успешно принят.');
			}

			$this->logger()->error('api_catalog_mode_file: ошибка записи файла - ' . $schema_upload_file_path);
			$this->api_response_by_type('failure', 'Не удалось записать файл: ' . $schema_upload_file_path);
		}

		$this->logger()->info('api_catalog_mode_file: file empty - ' . $schema_upload_file_path);
		$this->api_response_by_type('failure', 'Пришли пустые данные. Повторите попытку.');
	}

	/**
	 * Catalog import
	 */
	private function api_catalog_mode_import()
	{
		if($this->api_check_auth_key() === false)
		{
			$this->api_response_by_type('failure', __('Authorization failed', 'wc1c'));
		}

		$this->logger()->info('api_catalog_mode_import: start');

		if(wc1c_get_var($_GET['filename'], '') === '')
		{
			$this->logger()->warning('Import filename: is empty');
			$this->api_response_by_type('failure', __('Import filename is empty.', 'wc1c'));
		}

		$filename = wc1c_get_var($_GET['filename']);

		$file = $this->get_upload_directory() . '/catalog/' . sanitize_file_name($filename);

		try
		{
			$result_import = $this->file_import($file);

			if($result_import !== false)
			{
				$this->logger()->info('api_catalog_mode_import: end');
				$this->api_response_by_type('success', 'Импорт успешно завершен.');
			}
		}
		catch(Exception $e)
		{
			$this->logger()->error('api_catalog_mode_import: end', $e);
		}

		$this->api_response_by_type('failure', 'Импорт завершен с ошибкой.');
	}

	/**
	 * Импорт указанного файла
	 *
	 * @param $file_path
	 *
	 * @throws Exception
	 *
	 * @return mixed
	 */
	private function file_import($file_path)
	{
		$this->logger()->info('file_import: start');

		$type_file = $this->file_type_detect($file_path);

		$this->logger()->info('file_import: type_file - ' . $type_file);

		if(is_file($file_path) && $type_file !== '')
		{
			if(!defined('LIBXML_VERSION'))
			{
				throw new Exception('file_import: LIBXML_VERSION not defined, end & false');
			}

			if(!function_exists('libxml_use_internal_errors'))
			{
				throw new Exception('file_import: libxml_use_internal_errors, end & false');
			}

			libxml_use_internal_errors(true);

			$xml_data = simplexml_load_file($file_path);

			if(!$xml_data)
			{
				$this->logger()->error('file_import: xml errors', libxml_get_errors());
				return false;
			}

			try
			{
				$this->check_cml($xml_data);
			}
			catch(Exception $e)
			{
				throw new Exception('file_import: exception - ' . $e->getMessage());
			}

			if($this->get_options('skip_file_processing', 'yes') === 'yes')
			{
				$this->logger()->info('file_import: skip, end & true');
				return true;
			}

			/**
			 * Классификатор
			 *
			 * cml:Классификатор
			 */
			if($xml_data->Классификатор)
			{
				$this->logger()->info('file_import: classifier_processing start');

				try
				{
					$this->parse_xml_classifier($xml_data->Классификатор);
				}
				catch(Exception $e)
				{
					$this->logger()->error('file_import: exception - ' . $e->getMessage());
					return false;
				}

				$this->logger()->info('file_import: classifier_processing end');
			}

			/**
			 * Каталог
			 *
			 * cml:Каталог
			 */
			if($xml_data->Каталог)
			{
				$this->logger()->info('file_import: catalog_processing start');

				try
				{
					$this->parse_xml_catalog($xml_data->Каталог);
				}
				catch(Exception $e)
				{
					$this->logger()->info('file_import:exception - ' . $e->getMessage());
					return false;
				}

				$this->logger()->info('file_import: catalog_processing end, success');
			}

			/**
			 * Предложения
			 *
			 * cml:ПакетПредложений
			 */
			if($xml_data->ПакетПредложений)
			{
				$this->logger()->info('file_import: offers_package_processing start');

				try
				{
					$this->parse_xml_offers_package($xml_data->ПакетПредложений);
				}
				catch(Exception $e)
				{
					$this->logger()->info('file_import: exception - ' . $e->getMessage());
					return false;
				}

				$this->logger()->info('file_import: offers_package_processing end, success');
			}

			if($this->get_options('delete_files_after_import', 'no') === 'yes')
			{
				$this->logger()->info('file_import: delete file - ' . $file_path);
				unlink($file_path);
			}

			$this->logger()->info('file_import: end & true');
			return true;
		}

		$this->logger()->info('file_import: end & false');
		return false;
	}

	/**
	 * Загрузка каталога
	 *
	 * Каталог товаров содержит перечень товаров. Может составляться разными предприятиями (например, каталог продукции фирмы «1С»).
	 * У каталога всегда определен владелец, а товары могут описываться по классификатору.
	 *
	 * @throws Exception
	 *
	 * @param $xml_data
	 *
	 * @return bool
	 */
	private function parse_xml_catalog($xml_data)
	{
		// Глобально уникальный идентификатор каталога (рекомендуется использовать GUID)
		$data['catalog_guid'] = (string) $xml_data->Ид;

		// Идентификатор классификатора, в соответствии с которым описываются товары
		$data['classifier_guid'] = (string) $xml_data->ИдКлассификатора;

		// Наименование каталога
		$data['catalog_name'] = (string) $xml_data->Наименование;

		// Описание каталога
		$data['catalog_description']= '';
		if($xml_data->Описание)
		{
			$data['catalog_description'] = (string) $xml_data->Описание;
		}

		$this->logger()->info('parse_xml_catalog: catalog_guid ' . $data['catalog_guid']);
		$this->logger()->info('parse_xml_catalog: classifier_guid ' . $data['classifier_guid']);
		$this->logger()->info('parse_xml_catalog: catalog_name ' . $data['catalog_name']);

		/**
		 * Импорт товаров
		 */
		if($xml_data->Товары)
		{
			return true;
		}

		return false;
	}

	/**
	 * Разбор пакета предложений
	 *
	 * @param $xml_data
	 *
	 * @return bool
	 */
	private function parse_xml_offers_package($xml_data)
	{
		$offers_pack['offers_package_name'] = (string) $xml_data->Наименование;
		$offers_pack['offers_package_guid'] = (string) $xml_data->Ид;
		$offers_pack['catalog_guid'] = (string) $xml_data->ИдКаталога;
		$offers_pack['classifier_guid'] = (string) $xml_data->ИдКлассификатора;

		/*
		 * Описание пакета педложений
		 */
		$data['offers_package_description']= '';
		if($xml_data->Описание)
		{
			$data['offers_package_description'] = (string)$xml_data->Описание;
		}

		/*
		 * Загрузка предложений
		 */
		if($xml_data->Предложения)
		{
			$this->logger()->info('parse_xml_offers_package: $xml_data->Предложения start');

			$this->logger()->info('parse_xml_offers_package: $xml_data->Предложения end');
		}

		return true;
	}

	/**
	 * Проверка файла по стандарту
	 *
	 * @param $xml
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	private function check_cml($xml)
	{
		if($xml['ВерсияСхемы'])
		{
			$this->current_data['xml_version_schema'] = (string)$xml['ВерсияСхемы'];
			return true;
		}

		throw new Exception('check_cml: schema is not valid');
	}

	/**
	 * Определение типа файла
	 *
	 * @param $file_name
	 *
	 * @return string
	 */
	private function file_type_detect($file_name)
	{
		$types = array('import', 'offers', 'prices', 'rests', 'import_files');
		foreach($types as $type)
		{
			$pos = stripos($file_name, $type);
			if($pos !== false)
			{
				return $type;
			}
		}
		return '';
	}

	/**
	 * Обработка классификатора
	 *
	 * @throws
	 *
	 * @param $xml_data
	 *
	 * @return array|bool
	 */
	private function parse_xml_classifier($xml_data)
	{
		$data['classifier_guid'] = (string)$xml_data->Ид;
		$data['classifier_name'] = (string)$xml_data->Наименование;

		$this->logger()->info('parse_xml_classifier: classifier_guid ' . $data['classifier_guid']);
		$this->logger()->info('parse_xml_classifier: classifier_name ' . $data['classifier_name']);

		/**
		 * Группы
		 * Определяет иерархическую структуру групп номенклатуры
		 *
		 * cml:Группа
		 */
		if($xml_data->Группы)
		{
			$this->logger()->info('parse_xml_classifier: classifier_processing_groups start');


			$this->logger()->info('parse_xml_classifier: classifier_processing_groups end, success');
		}

		/**
		 * Свойства
		 * Содержит коллекцию свойств, значения которых можно или нужно указать ДЛЯ ВСЕХ товаров в
		 * каталоге, пакете предложений, документах
		 *
		 * cml:Свойство
		 */
		if($xml_data->Свойства)
		{
			$this->logger()->info('parse_xml_classifier: classifier_processing_properties start');


			$this->logger()->info('parse_xml_classifier: classifier_processing_properties end, success');
		}

		return $data;
	}

	/**
	 * Распаковка ZIP архива
	 *
	 * @param $zip_file_path
	 *
	 * @return boolean|int
	 */
	private function extract_zip($zip_file_path)
	{
		$zip_archive = zip_open($zip_file_path);

		$img_files = 0;
		$error_files = 0;

		if(is_resource($zip_archive))
		{
			$this->logger()->info('Unpack start: ' . $zip_file_path);

			while($zip_entry = zip_read($zip_archive))
			{
				$name = zip_entry_name($zip_entry);

				$this->logger()->info('Unpack file name: ' . $name);

				$import_files = $this->file_type_detect($name);

				if($import_files == 'import_files')
				{
					$result = $this->extract_zip_image($zip_archive, $zip_entry, substr($name, $import_files));

					if($result == false)
					{
						$error_files++;
					}

					$img_files++;
				}
				else
				{
					$result = $this->extract_zip_xml($zip_archive, $zip_entry, $name);

					if($result == false)
					{
						$error_files++;
					}
				}
			}

			$this->logger()->info('Unpack end: ' . $zip_file_path);

			zip_close($zip_archive);
		}
		else
		{
			$this->logger()->error('Zip_open error: ' . $zip_file_path);
			return false;
		}

		if($img_files > 0)
		{
			$this->logger()->info('Unpack images count: ' . $img_files);
		}
		
		if($error_files > 0)
		{
			$this->logger()->error('Unpack error files: ' . $img_files);
			return false;
		}

		return true;
	}

	/**
	 * @param $zip_arc
	 * @param $zip_entry
	 * @param $name
	 *
	 * @return boolean
	 */
	private function extract_zip_xml($zip_arc, $zip_entry, $name)
	{
		$uploads_files_dir = WC1C()->environment()->get('wc1c_current_schema_upload_directory'). '/catalog/';

		/**
		 * Directory
		 */
		if(substr($name, -1) == "/")
		{
			if(is_dir($uploads_files_dir . $name))
			{
				$this->logger()->info('Каталог существует: ' . $name);
			}
			else
			{
				$this->logger()->info('Создан каталог: ' . $name);
				@mkdir($uploads_files_dir . $name, 0775, true);
				if(!is_dir($uploads_files_dir . $name))
				{
					return false;
				}
			}
		}
		elseif(zip_entry_open($zip_arc, $zip_entry, "r"))
		{
			/**
			 * File data
			 */
			$dump = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

			/**
			 * Файл существует
			 */
			if(file_exists($uploads_files_dir . $name))
			{
				unlink($uploads_files_dir . $name);
				$this->logger()->info('Удален старый файл: ' . $uploads_files_dir . $name);
			}

			if($fd = @fopen($uploads_files_dir . $name, "wb"))
			{
				$xmlFiles[] = $uploads_files_dir . $name;

				$this->logger()->info('Создан файл: ' . $uploads_files_dir . $name);

				fwrite($fd, $dump);
				fclose($fd);
			}
			else
			{
				$this->logger()->info('Ошибка создания и открытия на запись: ' . $uploads_files_dir . $name);
			}

			zip_entry_close($zip_entry);
		}

		return true;
	}

	/**
	 * Images extract from zip
	 *
	 * @param $zipArc
	 * @param $zip_entry
	 * @param $name
	 *
	 * @return boolean
	 */
	private function extract_zip_image($zipArc, $zip_entry, $name)
	{
		/**
		 * Extract to dir
		 */
		$import_files_dir = $this->get_upload_directory() . '/catalog/import_files/';

		/**
		 * Dir
		 */
		if(substr($name, -1) == "/")
		{
			if(!is_dir($import_files_dir . $name))
			{
				mkdir($import_files_dir . $name, 0775, true);
				if(!is_dir($import_files_dir . $name))
				{
					return false;
				}
			}
		}
		/**
		 * File
		 */
		elseif(zip_entry_open($zipArc, $zip_entry, "r"))
		{
			/**
			 * File body
			 */
			$dump = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

			/**
			 * Logger
			 */
			$this->logger()->info('Extract image: ' . $name);

			/**
			 * Если файл существует
			 */
			if(is_file($import_files_dir . $name))
			{
				/**
				 * Получаем размеры файлов
				 */
				$size_dump = strlen($dump);
				$size_file = filesize($import_files_dir . $name);

				/**
				 * Новое изображение имеет отличия
				 */
				if($size_dump !== $size_file)
				{
					$this->logger()->info('Файл: ' . $name . ' существует, но старый! Старый размер ' . $size_file . ', новый ' . $size_dump);

					/**
					 * Открываем старый файл
					 */
					$fd = @fopen($import_files_dir . $name, "wb");

					if($fd === false)
					{
						$this->logger()->error('Ошибка открытия файла: ' . $import_files_dir . $name);
						return false;
					}

					/**
					 * Записываем новые данные и закрываем дескриптор
					 */
					fwrite($fd, $dump);
					fclose($fd);

					$this->logger()->info('Файл: ' . $name . ' перезаписан.');
				}
			}
			else
			{
				/**
				 * PHP?
				 */
				$pos = strpos($dump, "<?php");

				if($pos !== false)
				{
					$this->logger()->error('Ошибка записи файла: ' . $import_files_dir . $name . '! Он является PHP скриптом и не будет записан!');
				}
				else
				{
					$fd = @fopen($import_files_dir . $name, "wb");

					if($fd === false)
					{
						$this->logger()->error('Ошибка открытия файла: ' . $import_files_dir . $name . ' Проверьте права доступа!');
						return false;
					}

					fwrite($fd, $dump);
					fclose($fd);

					$this->logger()->info('Создан файл: ' . $import_files_dir . $name);
				}
			}

			zip_entry_close($zip_entry);
		}

		$this->logger()->info('Распаковка изображения завершена!');

		return true;
	}

	/**
	 * Проверка дерева каталогов для загрузки файлов
	 *
	 * @param $path
	 * @param bool $current_dir
	 */
	private function clean_upload_file_tree($path, $current_dir = false)
	{
		foreach(explode('/', $path) as $name)
		{
			if(!$name)
			{
				continue;
			}
			if(file_exists($current_dir . $name))
			{
				if(is_dir($current_dir . $name))
				{
					$current_dir = $current_dir . $name . '/';
					continue;
				}
				unlink($current_dir . $name);
			}
			@mkdir($current_dir . $name);
			$current_dir = $current_dir . $name . '/';
		}
	}
}
<?php namespace Wc1c\Main\Schemas\Productscleanercml;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Traits\UtilityTrait;

/**
 * Receiver
 *
 * @package Wc1c\Main\Schemas\Productscleanercml
 */
final class Receiver
{
	use SingletonTrait;
	use UtilityTrait;

	/**
	 * @var Core Schema core
	 */
	protected $core;

	/**
	 * @return void
	 */
	public function initHandler()
	{
		add_action('wc1c_receiver_' . $this->core()->getId(), [$this, 'handler'], 10, 0);
		add_action('wc1c_schema_productscleanercml_catalog_handler_checkauth', [$this, 'handlerCheckauth'], 10, 0);
		add_action('wc1c_schema_productscleanercml_catalog_handler_init', [$this, 'handlerCatalogModeInit'], 10, 0);
		add_action('wc1c_schema_productscleanercml_catalog_handler_file', [$this, 'handlerCatalogModeFile'], 10, 0);
		add_action('wc1c_schema_productscleanercml_catalog_handler_import', [$this, 'handlerCatalogModeImport'], 10, 0);
		add_action('wc1c_schema_productscleanercml_catalog_handler_deactivate', [$this, 'handlerCatalogModeDeactivate'], 10, 0);
		add_action('wc1c_schema_productscleanercml_catalog_handler_complete', [$this, 'handlerCatalogModeComplete'], 10, 0);
	}

	/**
	 * @return Core
	 */
	public function core()
	{
		return $this->core;
	}

	/**
	 * @param Core $core
	 */
	public function setCore($core)
	{
		$this->core = $core;
	}

	/**
	 * Handler
	 */
	public function handler()
	{
		$this->core()->log()->info(__('Received new request for Receiver.', 'wc1c-main'));

		$mode = '';
		$type = '';

		if(wc1c()->getVar($_GET['get_param'], '') !== '' || wc1c()->getVar($_GET['get_param?type'], '') !== '')
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
				$mode = sanitize_text_field($_GET['mode']);
			}

			if(array_key_exists('type', $output))
			{
				$type = $output['type'];
			}
			elseif(isset($_GET['type']))
			{
				$type = sanitize_text_field($_GET['type']);
			}

			if($type === '')
			{
				$type = sanitize_text_field($_GET['get_param?type']);
			}
		}

		$this->core()->log()->debug(__('Received request params.', 'wc1c-main'), ['type' => $type, 'mode=' => $mode]);

		if($type === 'catalog' && $mode !== '')
		{
			do_action('wc1c_schema_productscleanercml_catalog_handler', $mode, $this);

			switch($mode)
			{
				case 'checkauth':
					do_action('wc1c_schema_productscleanercml_catalog_handler_checkauth', $this);
					break;
				case 'init':
					$this->handlerCheckauthKey(true);
					do_action('wc1c_schema_productscleanercml_catalog_handler_init', $this);
					break;
				case 'file':
					$this->handlerCheckauthKey(true);
					do_action('wc1c_schema_productscleanercml_catalog_handler_file', $this);
					break;
				case 'import':
					$this->handlerCheckauthKey(true);
					do_action('wc1c_schema_productscleanercml_catalog_handler_import', $this);
					break;
				case 'deactivate':
					$this->handlerCheckauthKey(true);
					do_action('wc1c_schema_productscleanercml_catalog_handler_deactivate', $this);
					break;
				case 'complete':
					$this->handlerCheckauthKey(true);
					do_action('wc1c_schema_productscleanercml_catalog_handler_complete', $this);
					break;
				default:
					do_action('wc1c_schema_productscleanercml_catalog_handler_none', $mode, $this);
					$this->sendResponseByType('failure', __('Catalog: mode not found.', 'wc1c-main'));
			}
		}

		do_action('wc1c_schema_productscleanercml_handler_none', $mode, $this);

		$response_description = __('Schema: action not found.', 'wc1c-main');
		$this->core()->log()->warning($response_description);
		$this->sendResponseByType('failure', $response_description);
	}

	/**
	 * Request for a successful catalog upload
	 *
	 * @return void
	 */
	public function handlerCatalogModeComplete()
	{
		$this->sendResponseByType('success');
	}

	/**
	 * Request to deactivate old items
	 *
	 * @return void
	 */
	public function handlerCatalogModeDeactivate()
	{
		$this->sendResponseByType('success');
	}

	/**
	 * Send response by type
	 *
	 * @param string $type
	 * @param string $description
	 */
	public function sendResponseByType($type = 'failure', $description = '')
	{
		if(has_filter('wc1c_schema_productscleanercml_receiver_send_response_type'))
		{
			$type = apply_filters('wc1c_schema_productscleanercml_receiver_send_response_type', $type, $this);
		}

		if(has_filter('wc1c_schema_productscleanercml_receiver_send_response_by_type_description'))
		{
			$description = apply_filters('wc1c_schema_productscleanercml_receiver_send_response_by_type_description', $description, $this, $type);
		}

		$this->core()->log()->info(__('In 1C was send a response of the type:', 'wc1c-main') . ' ' . $type);

		$headers= [];
		$headers['Content-Type'] = 'Content-Type: text/plain; charset=utf-8';

		if(has_filter('wc1c_schema_productscleanercml_receiver_send_response_by_type_headers'))
		{
			$headers = apply_filters('wc1c_schema_productscleanercml_receiver_send_response_by_type_headers', $headers, $this, $type);
		}

		$this->core()->log()->debug(__('Headers for response.', 'wc1c-main'), ['context' => $headers]);

		foreach($headers as $header)
		{
			header($header);
		}

		switch($type)
		{
			case 'success':
				echo 'success' . PHP_EOL;
				break;
			case 'progress':
				echo 'progress' . PHP_EOL;
				break;
			default:
				echo 'failure' . PHP_EOL;
		}

		if($description !== '')
		{
			echo $description;
		}
		exit;
	}

	/**
	 * @return array
	 */
	public function getCredentialsByServer()
	{
		$credentials = [];

		if(!isset($_SERVER['PHP_AUTH_USER']))
		{
			if(isset($_SERVER['REMOTE_USER']))
			{
				$remote_user = sanitize_text_field($_SERVER['REMOTE_USER']);

				if(isset($_SERVER['REDIRECT_REMOTE_USER']))
				{
					$remote_user = sanitize_text_field($_SERVER['REMOTE_USER']) ?: sanitize_text_field($_SERVER['REDIRECT_REMOTE_USER']);
				}
			}
			elseif(isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']))
			{
				$remote_user = sanitize_text_field($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
			}

			if(!isset($remote_user))
			{
				// todo: автоматическая запись необходимой записи в файл .htaccess с просьбой повторить выгрузку
				$this->core()->log('schemas')->critical(__('Server in CGI mode. Not detected the presence of an entry in the root .htaccess file on the subject of the contents of the lines.', 'wc1c-main'), ['lines' => "RewriteEngine On:\nRewriteCond %{HTTP:Authorization} ^(.*)\nRewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]"]);
				$this->core()->configuration()->setStatus('error');
				$this->core()->configuration()->save();
				$this->sendResponseByType('failure', __('Not specified the user. Check the server settings.', 'wc1c-main'));
			}

			$str_tmp = base64_decode(substr($remote_user, 6));

			if($str_tmp)
			{
				list($user_login, $user_password) = explode(':', $str_tmp);

				$credentials['login'] = $user_login;
				$credentials['password'] = $user_password;
			}

			return $credentials;
		}

		$credentials['login'] = sanitize_text_field($_SERVER['PHP_AUTH_USER']);
		$credentials['password'] = sanitize_text_field($_SERVER['PHP_AUTH_PW']);

		return $credentials;
	}

	/**
	 * Checkauth
	 */
	public function handlerCheckauth()
	{
		$credentials = $this->getCredentialsByServer();
		$validator = false;

		if(has_filter('wc1c_schema_productscleanercml_handler_checkauth_validate'))
		{
			$validator = apply_filters('wc1c_schema_productscleanercml_handler_checkauth_validate', $credentials);
		}

		if(true !== $validator)
		{
			if($credentials['login'] !== $this->core()->getOptions('user_login', ''))
			{
				$this->core()->log()->notice(__('Not a valid username.', 'wc1c-main'));
				$this->sendResponseByType('failure', __('Not a valid username.', 'wc1c-main'));
			}

			if($credentials['password'] !== $this->core()->getOptions('user_password', ''))
			{
				$this->core()->log()->notice(__('Not a valid user password.', 'wc1c-main'));
				$this->sendResponseByType('failure', __('Not a valid user password.', 'wc1c-main'));
			}
		}

		$lines = [];

		if(session_status() === PHP_SESSION_NONE)
		{
			$this->core()->log()->debug(__('PHP session none, start new PHP session.', 'wc1c-main'));
			session_start();
		}

		$session_name = session_name();
		$session_id = session_id();

		$this->core()->log()->debug(__('Request authorization from 1C successfully completed.', 'wc1c-main'), ['session_name' => $session_name, 'session_id' => $session_id]);

		$lines['success'] = 'success' . PHP_EOL;
		$lines['session_name'] = $session_name . PHP_EOL;
		$lines['session_id'] = $session_id . PHP_EOL;

		$lines['bitrix_sessid'] = 'sessid=' . $session_id . PHP_EOL;
		$lines['timestamp'] = 'timestamp=' . time() . PHP_EOL;

		if(has_filter('wc1c_schema_productscleanercml_handler_checkauth_lines'))
		{
			$lines = apply_filters('wc1c_schema_productscleanercml_handler_checkauth_lines', $lines);
		}

		$this->core()->log()->debug(__('Print lines for 1C.', 'wc1c-main'), ['data' => $lines]);

		foreach($lines as $line)
		{
			echo $line;
		}
		die();
	}

	/**
	 * Authorization key verification
	 *
	 * @param bool $send_response
	 *
	 * @return bool
	 */
	public function handlerCheckauthKey($send_response = false)
	{
		if($this->core()->getOptions('receiver_check_auth_key_disabled', 'no') === 'yes')
		{
			$this->core()->log()->info(__('Authorization key verification is disabled. Lazy signature verification activated.', 'wc1c-main'));

			if(!isset($_GET['lazysign']))
			{
				$warning = __('Authorization key verification failed. 1C did not send the name of the lazy signature.', 'wc1c-main');
				$this->core()->log()->warning($warning);

				if($send_response)
				{
					$this->sendResponseByType('failure', $warning);
				}

				return false;
			}

			$lazy_sign = $_GET['lazysign'];
			$lazy_sign_store = $this->core()->configuration()->getMeta('receiver_lazy_sign');

			if($lazy_sign_store !== $lazy_sign)
			{
				$warning = __('Authorization key verification failed. 1C sent an incorrect lazy signature.', 'wc1c-main');
				$this->core()->log()->warning($warning);

				if($send_response)
				{
					$this->sendResponseByType('failure', $warning);
				}

				return false;
			}

			return true;
		}

		$session_name = session_name();

		if(!isset($_COOKIE[$session_name]))
		{
			$warning = __('Authorization key verification failed. 1C sent an empty session name.', 'wc1c-main');
			$this->core()->log()->warning($warning);

			if($send_response)
			{
				$this->sendResponseByType('failure', $warning);
			}

			return false;
		}

		if(session_status() === PHP_SESSION_NONE)
		{
			$this->core()->log()->debug(__('PHP session none, start new PHP session.', 'wc1c-main'));
			session_start();
		}

		$session_id = session_id();

		if($_COOKIE[$session_name] !== $session_id)
		{
			$warning = __('Authorization check failed - session id differs from the original.', 'wc1c-main');

			$this->core()->log()->warning($warning, ['client_session_id' => $_COOKIE[$session_name], 'server_session_id' => $session_id]);

			if($send_response)
			{
				$this->sendResponseByType('failure', $warning);
			}

			return false;
		}

		return true;
	}

	/**
	 * Init
	 */
	public function handlerCatalogModeInit()
	{
		$this->core()->log()->info(__('Initialization of receiving requests from 1C.', 'wc1c-main'));

		if(has_filter('wc1c_schema_productscleanercml_handler_catalog_mode_init_session'))
		{
			$_SESSION = apply_filters('wc1c_schema_productscleanercml_handler_catalog_mode_init_session', $_SESSION, $this);
		}

		$this->core()->log()->debug(__('Session for receiving requests.', 'wc1c-main'), ['session'=> $_SESSION]);

		if(wc1c()->filesystem()->cleanDirectory($this->core()->getUploadDirectory()))
		{
			$this->core()->log()->info(__('The directory for temporary files was successfully cleared of old files.', 'wc1c-main'), ['directory' => $this->core()->getUploadDirectory()]);
		}
		else
		{
			$error = __('Failed to clear the temp directory of old files.', 'wc1c-main');
			$this->core()->log()->error($error, ['directory' => $this->core()->getUploadDirectory()]);
			$this->sendResponseByType('failure', $error);
		}

		$data['zip'] = 'zip=no' . PHP_EOL;

		$max_size = $this->utilityConvertFileSize(wc1c()->environment()->get('php_post_max_size'));
		$max_wc1c = $this->utilityConvertFileSize(wc1c()->settings('main')->get('php_post_max_size'));
		$max_configuration = $this->utilityConvertFileSize($this->core()->getOptions('php_post_max_size'));

		$this->core()->log()->debug(__('The maximum size of accepted files from 1C is assigned:', 'wc1c-main') . ' ' . size_format($max_size));

		if($max_wc1c && $max_wc1c < $max_size)
		{
			$max_size = $max_wc1c;
			$this->core()->log()->debug(__('Based on the global settings of WC1C, the size of received files has been reduced from 1C to:', 'wc1c-main') . ' ' . size_format($max_size));
		}

		if($max_configuration && $max_configuration < $max_size)
		{
			$max_size = $max_configuration;
			$this->core()->log()->debug(__('Based on the configuration settings of WC1C, the size of received files has been reduced from 1C to:', 'wc1c-main') . ' ' . size_format($max_size));
		}

		$data['file_limit'] = 'file_limit=' . $max_size . PHP_EOL;

		if(has_filter('wc1c_schema_productscleanercml_handler_catalog_mode_init_data'))
		{
			$data = apply_filters('wc1c_schema_productscleanercml_handler_catalog_mode_init_data', $data, $this);
		}

		$this->core()->log()->debug(__('Print lines for 1C.', 'wc1c-main'), ['data' => $data]);

		foreach($data as $line_id => $line)
		{
			echo $line;
		}
		exit;
	}

	/**
	 * Uploading files from 1C to a local directory
	 *
	 * @return void
	 * @throws Exception
	 */
	public function handlerCatalogModeFile()
	{
		$upload_directory = $this->core()->getUploadDirectory() . DIRECTORY_SEPARATOR;

		if(has_filter('wc1c_schema_productscleanercml_handler_catalog_mode_file_directory'))
		{
			$upload_directory = apply_filters('wc1c_schema_productscleanercml_handler_catalog_mode_file_directory', $upload_directory);
		}

		$upload_directory = wp_normalize_path($upload_directory);

		wc1c()->filesystem()->ensureDirectoryExists($upload_directory);

		if(!wc1c()->filesystem()->exists($upload_directory))
		{
			$response_description = __('Directory is unavailable:', 'wc1c-main') . ' ' . $upload_directory;

			$this->core()->log()->error($response_description, ['directory' => $upload_directory]);
			$this->sendResponseByType('failure', $response_description);
		}

		$filename = wc1c()->getVar($_GET['filename'], '');

		if(has_filter('wc1c_schema_productscleanercml_handler_catalog_mode_file_filename'))
		{
			$filename = apply_filters('wc1c_schema_productscleanercml_handler_catalog_mode_file_filename', $filename);
		}

		if(empty($filename))
		{
			$response_description = __('Filename is empty.', 'wc1c-main');
			$this->core()->log()->error($response_description);
			$this->sendResponseByType('failure', $response_description);
		}

		$upload_file_path = wp_normalize_path($upload_directory . $filename);

		$this->core()->log()->info(__('Saving data to a file named:', 'wc1c-main') . ' ' . $filename, ['file_path' => $upload_file_path]);

		if(strpos($filename, 'import_files') !== false)
		{
			wc1c()->filesystem()->ensureDirectoryExists(dirname($upload_file_path));
		}

		if(!wc1c()->filesystem()->isWritable($upload_directory))
		{
			$response_description = __('Directory is unavailable for write.', 'wc1c-main');

			$this->core()->log()->error($response_description, ['directory' => $upload_directory]);
			$this->sendResponseByType('failure', $response_description);
		}

		$file_data = false;
		if(function_exists('file_get_contents'))
		{
			$file_data = file_get_contents('php://input');
		}
		elseif(isset($GLOBALS['HTTP_RAW_POST_DATA']))
		{
			$file_data = &$GLOBALS['HTTP_RAW_POST_DATA'];
		}

		if(false === $file_data)
		{
			$response_description = __('The request contains no data to write to the file. Retry the upload.', 'wc1c-main');

			$this->core()->log()->error($response_description);
			$this->sendResponseByType('failure', $response_description);
		}

		if(wc1c()->filesystem()->exists($upload_file_path))
		{
			$this->core()->log()->info(__('The file exists. Write a data to the end of an existing file.', 'wc1c-main'));
		}

		$file_size = false;
		if($fp = fopen($upload_file_path, "ab"))
		{
			$file_size = fwrite($fp, $file_data);
		}

		if($file_size)
		{
			wc1c()->filesystem()->chmod($upload_file_path , 0755);

			$response_description = __('The data is successfully written to a file. Recorded data size:', 'wc1c-main') . ' '. size_format($file_size);

			$this->core()->log()->info($response_description, ['file_size' => $file_size]);
			$this->sendResponseByType('success', $response_description);
			return;
		}

		$response_description = __('Failed to write data to file.', 'wc1c-main');

		$this->core()->log()->error($response_description, ['file_path' => $upload_file_path]);
		$this->sendResponseByType('failure', $response_description);
	}

	/**
	 * Catalog import
	 */
	public function handlerCatalogModeImport()
	{
		$this->core()->log()->info(__('On request from 1C - started importing data from a file.', 'wc1c-main'));

		$filename = wc1c()->getVar($_GET['filename'], '');

		if($filename === '')
		{
			$response_description = __('1C sent an empty file name for data import.', 'wc1c-main');
			$this->core()->log()->warning($response_description);
			$this->sendResponseByType('failure', $response_description);
		}

		$file = wp_normalize_path($this->core()->getUploadDirectory() . DIRECTORY_SEPARATOR . $filename);

		if(!wc1c()->filesystem()->exists($file))
		{
			$response_description = __('File for import is not exists.', 'wc1c-main');
			$this->core()->log()->error($response_description);
			$this->sendResponseByType('success', $response_description);
		}

		try
		{
			$result_file_processing = $this->core()->fileProcessing($file);

			if($result_file_processing)
			{
				$response_description = __('Import of data from file completed successfully.', 'wc1c-main');
				$this->core()->log()->info($response_description, ['file_name' => $filename, 'file_path' => $file]);
				$this->sendResponseByType('success', $response_description);
			}
		}
		catch(Exception $e)
		{
			$response_description = __('Importing data from a file ended with an error:', 'wc1c-main') . ' ' . $e->getMessage();
			$this->core()->log()->error($response_description, ['exception' => $e]);
			$this->sendResponseByType('failure', $response_description);
		}

		$response_description = __('Importing data from a file ended with an error.', 'wc1c-main');
		$this->core()->log()->error($response_description);
		$this->sendResponseByType('failure', $response_description);
	}
}
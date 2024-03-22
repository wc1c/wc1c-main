<?php namespace Wc1c\Main\Schemas\Productscml;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Schemas\Abstracts\Cml\ReceiverAbstract;
use Wc1c\Main\Traits\CoreTrait;
use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Traits\UtilityTrait;
use Wc1c\Wc\Contracts\ImagesStorageContract;
use Wc1c\Wc\Entities\Image;
use Wc1c\Wc\Storage;

/**
 * Receiver
 *
 * @package Wc1c\Main\Schemas\Productscml
 */
final class Receiver extends ReceiverAbstract
{
	use SingletonTrait;
	use UtilityTrait;
	use CoreTrait;

	/**
	 * @return void
	 */
	public function initHandler()
	{
		add_action('wc1c_receiver_' . $this->core()->getId(), [$this, 'handler'], 10, 0);

		add_action('wc1c_schema_productscml_catalog_handler_checkauth', [$this, 'handlerCheckauth'], 10, 0);

		if('standard' === $this->core()->getOptions('directory_clean_mode', 'standard'))
		{
			add_action('wc1c_schema_productscml_catalog_handler_init', [$this, 'handlerCatalogDirectoryClean'], 10, 0);
		}

		add_action('wc1c_schema_productscml_catalog_handler_init', [$this, 'handlerCatalogModeInit'], 10, 0);

		add_action('wc1c_schema_productscml_catalog_handler_file', [$this, 'handlerCatalogModeFile'], 10, 0);
		add_action('wc1c_schema_productscml_catalog_handler_import', [$this, 'handlerCatalogModeImport'], 10, 0);
		add_action('wc1c_schema_productscml_catalog_handler_deactivate', [$this, 'handlerCatalogModeDeactivate'], 10, 0);
		add_action('wc1c_schema_productscml_catalog_handler_complete', [$this, 'handlerCatalogModeComplete'], 10, 0);
	}

	/**
	 * Handler
	 */
	public function handler()
	{
		$this->core()->log()->info(__('Received new request from 1C for Receiver.', 'wc1c-main'));

		$mode_and_type = $this->detectModeAndType();
		$mode = $mode_and_type['mode'];
		$type = $mode_and_type['type'];

		$this->core()->log()->debug(__('The resulting query parameters.', 'wc1c-main'), ['type' => $type, 'mode=' => $mode]);

        $this->core()->configuration()->addMetaData('_receiver_mode', $mode, true);
        $this->core()->configuration()->addMetaData('_receiver_type', $type, true);
        $this->core()->configuration()->saveMetaData();

		if($type === 'catalog' && $mode !== '')
		{
			do_action('wc1c_schema_productscml_catalog_handler', $mode, $this);

			switch($mode)
			{
				case 'checkauth':
					do_action('wc1c_schema_productscml_catalog_handler_checkauth', $this);
					break;
				case 'init':
					$this->handlerCheckauthKey(true);
					do_action('wc1c_schema_productscml_catalog_handler_init', $this);
					break;
				case 'file':
					$this->handlerCheckauthKey(true);
					do_action('wc1c_schema_productscml_catalog_handler_file', $this);
					break;
				case 'import':
					$this->handlerCheckauthKey(true);
					do_action('wc1c_schema_productscml_catalog_handler_import', $this);
					break;
				case 'deactivate':
					$this->handlerCheckauthKey(true);
					do_action('wc1c_schema_productscml_catalog_handler_deactivate', $this);
					break;
				case 'complete':
					$this->handlerCheckauthKey(true);
					do_action('wc1c_schema_productscml_catalog_handler_complete', $this);
					break;
				default:
					do_action('wc1c_schema_productscml_catalog_handler_none', $mode, $this);
					$this->sendResponseByType('failure', __('Catalog: mode not found.', 'wc1c-main'));
			}
		}

		do_action('wc1c_schema_productscml_handler_none', $mode, $this);

		$response_description = __('Action is not found in schema.', 'wc1c-main');

		$this->core()->log()->warning($response_description);

		$this->sendResponseByType($this->core()->getOptions('response_unknown_action', 'failure'), $response_description);
	}

	/**
	 * Request for a successful catalog upload
	 *
	 * @return void
	 */
	public function handlerCatalogModeComplete()
	{
        $this->core()->log()->notice(__('Sending a successful completion of the exchange in 1C.', 'wc1c-main'));

        $this->sendResponseByType('success');
	}

	/**
	 * Request to deactivate old items
	 *
	 * @return void
	 */
	public function handlerCatalogModeDeactivate()
	{
        if(isset($_GET['timestamp']))
        {
            $timestamp = (int)$_GET['timestamp'];

            $this->core()->log()->notice(__('The time of the last full exchange has been set.', 'wc1c-main'), ['timestamp' => $timestamp]);

            $this->core()->configuration()->addMetaData('_catalog_full_time', $timestamp, true);
            $this->core()->configuration()->saveMetaData();
        }

		$this->sendResponseByType('success');
	}

	/**
	 * Send response by type
	 *
	 * @param string $type
	 * @param string $description
	 */
	public function sendResponseByType(string $type = 'failure', string $description = '')
	{
		if(has_filter('wc1c_schema_productscml_receiver_send_response_type'))
		{
			$type = apply_filters('wc1c_schema_productscml_receiver_send_response_type', $type, $this);
		}

		if($this->core()->configuration()->isEnabled())
		{
			$status = $this->core()->configuration()->getStatus();

			if($type === 'success')
			{
				$status = 'active';
			}

			if($type === 'failure')
			{
				$status = 'error';
			}

			$this->core()->configuration()->setStatus($status);
			$this->core()->configuration()->save();
		}

		if(has_filter('wc1c_schema_productscml_receiver_send_response_by_type_description'))
		{
			$description = apply_filters('wc1c_schema_productscml_receiver_send_response_by_type_description', $description, $this, $type);
		}

		$headers= [];
		$headers['Content-Type'] = 'Content-Type: text/plain; charset=utf-8';

		if(has_filter('wc1c_schema_productscml_receiver_send_response_by_type_headers'))
		{
			$headers = apply_filters('wc1c_schema_productscml_receiver_send_response_by_type_headers', $headers, $this, $type);
		}

        if(!headers_sent())
        {
            $this->core()->log()->debug(__('Headers for response.', 'wc1c-main'), ['context' => $headers]);

            foreach($headers as $header)
            {
                header($header);
            }
        }

        $this->core()->log()->info(sprintf('%s %s.', __('In 1C was send a response of the type:', 'wc1c-main'),  $type), ['type' => $type]);

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
			printf('%s', wp_kses_post($description));
		}
		exit;
	}

	/**
	 * @return array
	 */
	public function getCredentialsByServer(): array
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
				$descriptions = __('Server in CGI mode. Not detected the presence of an entry in the root .htaccess file on the subject of the contents of the lines.', 'wc1c-main');

				$this->core()->log('schemas')->critical($descriptions, ['lines' => "RewriteEngine On:\nRewriteCond %{HTTP:Authorization} ^(.*)\nRewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]"]);

				$this->sendResponseByType('failure', $descriptions);
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

		if(has_filter('wc1c_schema_productscml_handler_checkauth_validate'))
		{
			$validator = apply_filters('wc1c_schema_productscml_handler_checkauth_validate', $credentials);
		}

		if(true !== $validator)
		{
			if($credentials['login'] !== $this->core()->getOptions('user_login', ''))
			{
                $message = __('Not a valid username.', 'wc1c-main');

				$this->core()->log()->warning($message);

				$this->sendResponseByType('failure', $message);
			}

			if($credentials['password'] !== $this->core()->getOptions('user_password', ''))
			{
                $message = __('Not a valid user password.', 'wc1c-main');

				$this->core()->log()->warning($message);

				$this->sendResponseByType('failure', $message);
			}
		}

		$lines = [];

		$session_name = session_name();

		if(session_status() === PHP_SESSION_NONE)
		{
			$this->core()->log()->debug(__('PHP session none, start new PHP session.', 'wc1c-main'));
			session_start();
		}

		$session_id = session_id();

		$this->core()->configuration()->addMetaData('session_name', maybe_serialize($session_name), true);
		$this->core()->configuration()->addMetaData('session_id', maybe_serialize($session_id), true);

		$this->core()->configuration()->saveMetaData();

		$this->core()->log()->info(__('1C authorization has been successfully passed.', 'wc1c-main'), ['session_name' => $session_name, 'session_id' => $session_id]);

		$lines['success'] = 'success' . PHP_EOL;
		$lines['session_name'] = $session_name . PHP_EOL;
		$lines['session_id'] = $session_id . PHP_EOL;

		$lines['bitrix_sessid'] = 'sessid=' . $session_id . PHP_EOL;
		$lines['timestamp'] = 'timestamp=' . current_time('timestamp', true) . PHP_EOL;

		if(has_filter('wc1c_schema_productscml_handler_checkauth_lines'))
		{
			$lines = apply_filters('wc1c_schema_productscml_handler_checkauth_lines', $lines);
		}

		$this->core()->log()->debug(__('Print lines for 1C.', 'wc1c-main'), $lines);

        if($this->core()->configuration()->isStatus('processing'))
        {
            $this->core()->configuration()->setStatus('active');
            $this->core()->configuration()->save();
        }

		foreach($lines as $line)
		{
			printf('%s', wp_kses_post($line));
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
	public function handlerCheckauthKey(bool $send_response = false): bool
	{
        if('yes' === $this->core()->getOptions('browser_debug', 'no'))
        {
            return true;
        }

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

		$lazy_sign = sanitize_text_field($_GET['lazysign']);
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

		$session_name = sanitize_text_field($this->core()->configuration()->getMeta('session_name'));

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

		$session_id = sanitize_text_field($this->core()->configuration()->getMeta('session_id'));

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

		if(session_status() === PHP_SESSION_NONE)
		{
			session_id($session_id);

			$this->core()->log()->debug(__('PHP session none, restart last PHP session.', 'wc1c-main'), ['session_id' => $session_id]);
			session_start();
		}

		return true;
	}

	/**
	 * Cleaning the directory for temporary files.
	 *
	 * @return void
	 */
	public function handlerCatalogDirectoryClean()
	{
		$directory = $this->core()->getUploadDirectory();

		$this->core()->log()->info(__('Cleaning the directory for temporary files.', 'wc1c-main'), ['directory' => $directory]);

		wc1c()->filesystem()->ensureDirectoryExists($directory);

		if(wc1c()->filesystem()->cleanDirectory($directory))
		{
			$this->core()->log()->notice(__('Cleaning the directory for temporary files as completed.', 'wc1c-main'), ['directory' => $this->core()->getUploadDirectory()]);
		}
		else
		{
			$error = __('Cleaning the directory for temporary files as failed.', 'wc1c-main');

			$this->core()->log()->error($error, ['directory' => $directory]);

			$this->sendResponseByType('failure', $error);
		}
	}

	/**
	 * Init
	 */
	public function handlerCatalogModeInit()
	{
		$this->core()->log()->info(__('Initialization of receiving requests from 1C.', 'wc1c-main'));

		if(has_filter('wc1c_schema_productscml_handler_catalog_mode_init_session'))
		{
			$_SESSION = apply_filters('wc1c_schema_productscml_handler_catalog_mode_init_session', $_SESSION, $this);

			$this->core()->log()->info(__('Session for receiving requests is changed by external algorithms.', 'wc1c-main'), ['session'=> $_SESSION]);
		}

		$directory = $this->core()->getUploadDirectory();

		$this->core()->log()->info(__('Check the directory for temporary files.', 'wc1c-main'), ['directory' => $directory]);

		wc1c()->filesystem()->ensureDirectoryExists($directory);

		if(!wc1c()->filesystem()->isDirectory($directory))
		{
			$error = __('Failed to check the temp directory.', 'wc1c-main');

			$this->core()->log()->error($error, ['directory' => $directory]);

			$this->sendResponseByType('failure', $error);
		}
		else
		{
			$ht_name = $directory . '/.htaccess';
			if(!file_exists($ht_name))
			{
				$fp = fopen($ht_name, 'wb');
				if($fp)
				{
					fwrite($fp, "Deny from All");
					fclose($fp);
				}
			}
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

		if(has_filter('wc1c_schema_productscml_handler_catalog_mode_init_data'))
		{
			$data = apply_filters('wc1c_schema_productscml_handler_catalog_mode_init_data', $data, $this);
		}

		$this->core()->log()->debug(__('Print lines for 1C.', 'wc1c-main'), ['data' => $data]);

		foreach($data as $line_id => $line)
		{
			printf('%s', wp_kses_post($line));
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

		if(has_filter('wc1c_schema_productscml_handler_catalog_mode_file_directory'))
		{
			$upload_directory = apply_filters('wc1c_schema_productscml_handler_catalog_mode_file_directory', $upload_directory);
		}

		$upload_directory = wp_normalize_path($upload_directory);

		wc1c()->filesystem()->ensureDirectoryExists($upload_directory);

		if(!wc1c()->filesystem()->exists($upload_directory))
		{
			$response_description = sprintf('%s %s', __('Directory is unavailable:', 'wc1c-main'), $upload_directory);

			$this->core()->log()->error($response_description, ['directory' => $upload_directory]);

			$this->sendResponseByType('failure', $response_description);
		}

		$filename = wc1c()->getVar($_GET['filename'], '');

		if(has_filter('wc1c_schema_productscml_handler_catalog_mode_file_filename'))
		{
			$filename = apply_filters('wc1c_schema_productscml_handler_catalog_mode_file_filename', $filename);
		}

		if(empty($filename))
		{
			$response_description = __('Filename is empty.', 'wc1c-main');

			$this->core()->log()->error($response_description);

			$this->sendResponseByType('failure', $response_description);
		}

		$upload_file_path = wp_normalize_path($upload_directory . $filename);

		$this->core()->log()->info(sprintf('%s %s', __('Writing data to a file named:', 'wc1c-main'), $filename), ['file_path' => $upload_file_path]);

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

            $file_extension = wc1c()->filesystem()->extension($upload_file_path);
            $file_hash = wc1c()->filesystem()->hash($upload_file_path);

			$response_description = __('The data is successfully written to a file. Recorded data size:', 'wc1c-main') . ' '. size_format($file_size);

			/*
			 * Adding to media library
			 */
			if($file_extension !== 'xml' && 'yes' === $this->core()->getOptions('media_library_images_by_receiver', 'no'))
			{
				if('yes' !== $this->core()->getOptions('media_library', 'no'))
				{
					$this->core()->log()->warning(__('The file was not saved to the media library. Adding is disabled in the settings.', 'wc1c-main'));
				}
				else
				{
					$image = wp_get_image_mime($upload_file_path);
					if($image)
					{
						/** @var ImagesStorageContract $images_storage */
						$images_storage = Storage::load('image');

						$image_file_name = explode('.', basename($upload_file_path));

						$image_current = $images_storage->getByExternalName($image_file_name[0]);
						if(is_array($image_current))
						{
							$image_current = $image_current[0];
						}

                        if($image_current)
                        {
                            $current_file_extension = $image_current->getMeta('_wc1c_external_image_extension', true);
                            if(is_array($current_file_extension))
                            {
                                $current_file_extension = reset($current_file_extension);
                            }

                            $current_file_hash = $image_current->getMeta('_wc1c_external_hash', true);
                            if(is_array($current_file_hash))
                            {
                                $current_file_hash = reset($current_file_hash);
                            }

                            if(!empty($current_file_extension) && $current_file_extension !== $file_extension)
                            {
                                $image_current = false;
                            }
                            elseif(empty($current_file_extension))
                            {
                                $image_current->addMetaData('_wc1c_external_image_extension', $file_extension);
                            }

                            if(!empty($current_file_hash) && $current_file_hash !== $file_hash)
                            {
                                $image_current = false;
                            }
                            elseif(empty($current_file_hash))
                            {
                                $image_current->addMetaData('_wc1c_external_hash', $file_hash);
                            }
                        }

						if(false === $image_current)
						{
							$new_image = new Image();
                            $this->core()->setImageTimes($new_image);

							$new_image->setName(__('No name', 'wc1c-main'));
							$new_image->setExternalName($image_file_name[0]);
							$new_image->setSlug($image_file_name[0]);

                            $new_image->addMetaData('_wc1c_external_image_extension', $file_extension);
                            $new_image->addMetaData('_wc1c_external_hash', $file_hash);

                            $new_image->setConfigurationId($this->core()->configuration()->getId());
							$new_image->setSchemaId($this->core()->getId());

							$new_image->setUserId($this->core()->configuration()->getUserId());
							$new_image->setMimeType($image);

							$image_id = $images_storage->uploadByPath($upload_file_path, $new_image);

							if($image_id === false)
							{
								$response_description .= '. ' . __('The image has not been added to the media library.', 'wc1c-main');
							}
							else
							{
								$response_description .= '. ' . __('Image added to media library, id:', 'wc1c-main') . ' ' . $image_id;
							}
						}
						else
						{
                            $image_current = $this->core()->setImageTimes($image_current);
                            $image_current->save();

							$response_description .= '. ' . __('The image has not been added to the media library. It was added earlier, id:', 'wc1c-main') . ' ' . $image_current->getId();
						}
					}
				}
			}

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
		$filename = wc1c()->getVar($_GET['filename'], '');

        $this->core()->log()->notice(__('On request from 1C - started importing data from a file.', 'wc1c-main'), ['file' => $filename]);

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
			$this->sendResponseByType('failure', $response_description);
		}

		try
		{
			$result_file_processing = $this->core()->fileProcessing($file);

			if($result_file_processing)
			{
				$response_description = __('Import of data from file completed successfully.', 'wc1c-main');

                $this->core()->log()->notice($response_description, ['file_name' => $filename, 'file_path' => $file]);
				$this->sendResponseByType('success', $response_description);
			}
		}
		catch(\Throwable $e)
		{
			$response_description = sprintf('%s %s', __('Importing data from a file ended with an error:', 'wc1c-main'), $e->getMessage());

            $this->core()->log()->error($response_description, ['exception' => $e]);

			$this->sendResponseByType('failure', $response_description);
		}

		$response_description = __('Importing data from a file ended with an error.', 'wc1c-main');

        $this->core()->log()->error($response_description);
		$this->sendResponseByType('failure', $response_description);
	}
}
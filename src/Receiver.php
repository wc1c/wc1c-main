<?php namespace Wc1c\Main;

use Wc1c\Main\Data\Entities\Configuration;

defined('ABSPATH') || exit;

/**
 * Receiver
 *
 * @package Wc1c\Main
 */
final class Receiver
{
	/**
	 * Receiver constructor.
	 */
	public function __construct()
	{
		do_action('wc1c_receiver_loaded');
	}

	/**
	 * Receiver register.
	 *
	 * @return void
	 */
	public function register()
	{
		if(false === wc1c()->context()->isReceiver())
		{
			return;
		}

		add_filter('parse_request', [$this, 'handleRequests']);
	}

	/**
	 * Handle requests
	 */
	public function handleRequests()
	{
		$_wc1c_receiver = sanitize_text_field($_GET['wc1c-receiver']);

		$wc1c_receiver = wc1c()->getVar($_wc1c_receiver, false);

		wc1c()->log('receiver')->info(__('Received new request for Receiver.', 'wc1c-main'));
		wc1c()->log('receiver')->debug(__('Receiver request params.', 'wc1c-main'), ['GET' => $_GET, 'POST' => $_POST, 'SERVER' => $_SERVER]);

		wc1c()->define('WC1C_RECEIVER_REQUEST', true);

		if('yes' !== wc1c()->settings()->get('receiver', 'yes'))
		{
			wc1c()->log('receiver')->warning(__('Receiver is offline. Request reject.', 'wc1c-main'));
			die(__('Receiver is offline. Request reject.', 'wc1c-main'));
		}

		try
		{
			$configuration = new Configuration($wc1c_receiver);
		}
		catch(\Throwable $e)
		{
			wc1c()->log('receiver')->warning(__('Selected configuration for Receiver is unavailable.', 'wc1c-main'), ['exception' => $e]);
			die(__('Configuration for Receiver is unavailable.', 'wc1c-main'));
		}

		try
		{
			$schema = wc1c()->schemas()->init($configuration);
		}
		catch(\Throwable $e)
		{
			wc1c()->log('receiver')->error('Schema for configuration is not initialized.', ['exception' => $e]);
			die(__('Schema for configuration is not initialized.', 'wc1c-main'));
		}

		wc1c()->environment()->set('current_configuration_id', $wc1c_receiver);

		if(method_exists($schema, 'receiver'))
		{
			wc1c()->log('receiver')->info(__('The request was successfully submitted for processing in the schema for the selected configuration.', 'wc1c-main'), ['action' => 'receiver']);

			$schema->receiver();

			return;
		}

		if($configuration->isEnabled() === false)
		{
			wc1c()->log('receiver')->warning(__('Selected configuration is offline.', 'wc1c-main'));
			die(__('Selected configuration is offline.', 'wc1c-main'));
		}

		try
		{
			$configuration->setDateActivity(time());
			$configuration->save();
		}
		catch(\Throwable $e)
		{
			wc1c()->log('receiver')->error('Error saving configuration.', ['exception' => $e]);
			die(__('Error saving configuration.', 'wc1c-main'));
		}

		$action = false;
		$wc1c_receiver_action = 'wc1c_receiver_' . $configuration->getSchema();

		if(has_action($wc1c_receiver_action))
		{
			$action = true;

			ob_start();
			nocache_headers();

			wc1c()->log('receiver')->info(__('The request was successfully submitted for processing in the schema for the selected configuration.', 'wc1c-main'), ['action' => $wc1c_receiver_action]);
			do_action($wc1c_receiver_action);

			ob_end_clean();
		}

		if(false === $action)
		{
			wc1c()->log('receiver')->warning(__('Receiver request is very bad! Action not found in selected configuration.', 'wc1c-main'), ['action' => $wc1c_receiver_action]);
			die(__('Receiver request is very bad! Action not found.', 'wc1c-main'));
		}
		die();
	}
}
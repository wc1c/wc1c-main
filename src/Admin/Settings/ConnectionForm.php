<?php namespace Wc1c\Main\Admin\Settings;

defined('ABSPATH') || exit;

use Wc1c\Main\Connection;
use Wc1c\Main\Exceptions\Exception;

/**
 * ConnectionForm
 *
 * @package Wc1c\Main\Admin
 */
class ConnectionForm extends Form
{
	/**
	 * @var bool Connection status
	 */
	public $status = false;

	/**
	 * @var Connection
	 */
	public $connection;

	/**
	 * ConnectionForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->setId('settings-connection');

		$settings = wc1c()->settings('connection');

		$this->setSettings($settings);

		try
		{
			$this->connection = new Connection();
			$this->connection->setAppName(get_bloginfo());
		}
		catch(\Throwable $e){}

		try
		{
			$this->apHandle();
		}
		catch(\Throwable $e){}

		if('' !== $settings->get('token', ''))
		{
			$this->status = true;
		}

		if($this->status !== false)
		{
			add_filter('wc1c_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_connected'], 10);
		}
		else
		{
			add_action('wc1c_admin_show', [$this, 'output'], 10);
		}

		$this->init();
	}

    /**
     * Handle AP
     *
     * @return void
     * @throws \Exception
     */
	public function apHandle()
	{
		if(isset($_GET['site_url'], $_GET['user_login']))
		{
			$site_url = urldecode(esc_url_raw($_GET['site_url']));
			$user_login = urldecode(sanitize_text_field($_GET['user_login']));
			$password = '';
			$sold_url = remove_query_arg(['site_url', 'user_login', 'password']);

			if(isset($_GET['password']))
			{
				$password = urldecode(sanitize_text_field($_GET['password']));

				$result_verify = $this->connection->verify($user_login, $password);

				if(true !== $result_verify)
				{
					$password = '';
				}
			}

			if($password !== '')
			{
				try
				{
					$this->settings->save(['token' => $password, 'login' => $user_login]);

					wc1c()->admin()->notices()->create
					(
						[
							'type' => 'update',
							'data' => sprintf
							(
								__( 'The user with the login %s on the site %s successfully connected to the current site.', 'wc1c-main'),
								'<strong>' . esc_html($user_login) . '</strong>',
								'<strong>' . esc_html($site_url) . '</strong>'
							)
						]
					);

					wp_safe_redirect($sold_url);
					die;
				}
				catch(\Throwable $e)
				{
					wc1c()->log()->addNotice('Settings is not successful save.', ['exception' => $e]);
				}
			}

			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => sprintf
					(
						__('Error connecting user with login %s on site %s to the current site. Please try again later.', 'wc1c-main'),
						'<strong>' . esc_html($user_login) . '</strong>',
						'<strong>' . esc_html($site_url) . '</strong>'
					)
				]
			);

			wp_safe_redirect($sold_url);
			die;
		}
	}

	/**
	 * Save
	 *
	 * @return bool
	 */
	public function save(): bool
	{
		$post_data = $this->getPostedData();

		if(!isset($post_data['_wc1c-admin-nonce']))
		{
			return false;
		}

		if(empty($post_data) || !wp_verify_nonce($post_data['_wc1c-admin-nonce'], 'wc1c-admin-settings-save'))
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Connection error. Please retry.', 'wc1c-main')
				]
			);

			return false;
		}

		if($this->status)
		{
			try
			{
				$this->settings->save(['login' => '', 'token' => '']);
			}
			catch(\Throwable $e)
			{
				wc1c()->log()->addNotice('Settings is not successful save.', ['exception' => $e]);
			}

			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'update',
					'data' => __('Disconnect successful. Reconnect is available', 'wc1c-main')
				]
			);

			$sold_url =  get_site_url() . add_query_arg('do_settings', 'connection');
		}
		else
		{
			$sold_url = $this->connection->buildUrl(get_site_url() . add_query_arg('do_settings', 'connection'));
		}

		wp_redirect($sold_url);
		die;
	}

	/**
	 * Connected fields
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_connected($fields): array
    {
		$fields['connected_title'] =
		[
			'title' => __('Site is connected to WC1C', 'wc1c-main'),
			'type' => 'title',
			'description' => __('To create a new connection, need to disconnect the current connection.', 'wc1c-main'),
		];

		$fields['login'] =
		[
			'title' => __('Username', 'wc1c-main'),
			'type' => 'text',
			'description' => __('Connected login from the WC1C website.', 'wc1c-main'),
			'default' => '',
			'disabled' => true,
			'css' => 'min-width: 300px;',
		];

		$fields['token'] =
		[
			'title' => __('App token', 'wc1c-main'),
			'type' => 'text',
			'description' => __('The current application token for the user. This token can be revoked in your personal account on the WC1C website, as well as by clicking the Disconnect button.', 'wc1c-main'),
			'default' => '',
			'disabled' => true,
			'css' => 'min-width: 300px;',
		];

		return $fields;
	}

	/**
	 * Form show
	 */
	public function outputForm()
	{
		$args =
		[
			'object' => $this
		];

		wc1c()->views()->getView('connection/form.php', $args);
	}

	/**
	 * Output
	 *
	 * @return void
	 */
	public function output()
	{
		wc1c()->views()->getView('connection/init.php');
	}
}
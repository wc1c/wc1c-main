<?php namespace Wc1c\Main\Schemas\Productscleanercml;

defined('ABSPATH') || exit;

use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Traits\UtilityTrait;

/**
 * Admin
 *
 * @package Wc1c\Main\Schemas\Productscleanercml
 */
class Admin
{
	use SingletonTrait;
	use UtilityTrait;

	/**
	 * @var Core Schema core
	 */
	protected $core;

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
	 * @return void
	 */
	public function initConfigurationsFields()
	{
		add_filter('wc1c_configurations-update_form_load_fields', [$this, 'configurationsFieldsReceiver'], 10, 1);

		add_filter('wc1c_configurations-update_form_load_fields', [$this, 'configurationsFieldsSync'], 30, 1);
		add_filter('wc1c_configurations-update_form_load_fields', [$this, 'configurationsFieldsClean'], 70, 1);

		add_filter('wc1c_configurations-update_form_load_fields', [$this, 'configurationsFieldsLogs'], 110, 1);
		add_filter('wc1c_configurations-update_form_load_fields', [$this, 'configurationsFieldsOther'], 120, 1);
	}

	/**
	 * Configurations fields: receiver
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function configurationsFieldsReceiver($fields)
	{
		$fields['title_receiver'] =
		[
			'title' => __('Receiving requests from 1C', 'wc1c-main'),
			'type' => 'title',
			'description' => __('Authorization of requests and regulation of algorithms for receiving requests for the Receiver from the 1C programs by CommerceML protocol.', 'wc1c-main'),
		];

		$lazy_sign = $this->core()->configuration()->getMeta('receiver_lazy_sign');

		if(empty($lazy_sign))
		{
			$lazy_sign = md5($this->core()->configuration()->getId() . time());
			$this->core()->configuration()->addMetaData('receiver_lazy_sign', $lazy_sign, true);
			$this->core()->configuration()->saveMetaData();
		}

		$url_raw = trim(get_site_url(null, '/?wc1c-receiver=' . $this->core()->configuration()->getId() . '&lazysign=' . $lazy_sign . '&get_param'));
		$url_raw = '<span class="d-block input-text mt-0 p-2 bg-light regular-input wc1c_urls">' . esc_url($url_raw) . '</span>';

		$fields['url_requests'] =
		[
			'title' => __('Website address', 'wc1c-main'),
			'type' => 'raw',
			'raw' => $url_raw,
			'description' => sprintf(
				'%s<hr>%s',
				__('Specified in the exchange settings on the 1C side. The Recipient is located at this address, which will receive requests from 1C.', 'wc1c-main'),
				__('When copying, you need to get rid of whitespace characters, if they are present.', 'wc1c-main')
			)
		];

		$fields['user_login'] =
		[
			'title' => __('Username', 'wc1c-main'),
			'type' => 'text',
			'description' => sprintf(
				'%s<hr>%s',
				__('Specified when setting up an exchange with a site on the 1C side. Any name can be specified, except for an empty value.', 'wc1c-main'),
				__('Work with data on the site is performed on behalf of the configuration owner, and not on behalf of the specified username.', 'wc1c-main')
			),
			'default' => '',
			'css' => 'min-width: 377px;',
		];

		$fields['user_password'] =
		[
			'title' => __('User password', 'wc1c-main'),
			'type' => 'password',
			'description' => __('Specified in pair with the username when setting up on the 1C side. It is advisable not to specify a password for the current WordPress user.', 'wc1c-main'),
			'default' => '',
			'css' => 'min-width: 377px;',
		];

		return $fields;
	}

	/**
	 * Configuration fields: other
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function configurationsFieldsOther($fields): array
	{
		$fields['title_other'] =
		[
			'title' => __('Other parameters', 'wc1c-main'),
			'type' => 'title',
			'description' => __('Change of data processing behavior for environment compatibility and so on.', 'wc1c-main'),
		];

		$fields['php_post_max_size'] =
		[
			'title' => __('Maximum size of accepted requests', 'wc1c-main'),
			'type' => 'text',
			'description' => sprintf
			(
				'%s<br />%s <b>%s</b><br />%s',
				__('Enter the maximum size of accepted requests from 1C at a time in bytes. May be specified with a dimension suffix, such as 7M, where M = megabyte, K = kilobyte, G - gigabyte.', 'wc1c-main'),
				__('Current WC1C limit:', 'wc1c-main'),
				wc1c()->settings()->get('php_post_max_size', wc1c()->environment()->get('php_post_max_size')),
				__('Can only decrease the value, because it must not exceed the limits from the WC1C settings.', 'wc1c-main')
			),
			'default' => wc1c()->settings()->get('php_post_max_size', wc1c()->environment()->get('php_post_max_size')),
			'css' => 'min-width: 100px;',
		];

		return $fields;
	}

	/**
	 * Configuration fields: sync
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function configurationsFieldsSync($fields): array
	{
		$fields['title_sync'] =
		[
			'title' => __('Sync', 'wc1c-main'),
			'type' => 'title',
			'description' => sprintf
			('%s %s',
			 __('Dispute resolution between existing products (goods) on the 1C side and in WooCommerce. For extended matching (example by SKU), must use the extension.', 'wc1c-main'),
				__('Products will only be deleted for found matching keys.', 'wc1c-main')
			),
		];

		$fields['sync_by_id'] =
		[
			'title' => __('By external ID from 1C', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Check the box to enable. Enabled by default.', 'wc1c-main'),
			'description' => sprintf
			(
				'%s<hr> %s',
				__('When creating new products based on data from 1C, a universal global identifier from 1C is filled in for them. Can also fill in global identifiers manually for manually created products.', 'wc1c-main'),
				__('Enabling the option allows you to use the filled External ID to mark products (goods) as existing, and thereby run algorithms to update them.', 'wc1c-main')
			),
			'default' => 'yes'
		];

		return $fields;
	}

	/**
	 * Configuration fields: clean
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function configurationsFieldsClean($fields): array
	{
		$fields['title_clean'] =
		[
			'title' => __('Cleaning', 'wc1c-main'),
			'type' => 'title',
			'description' => __('Comprehensive settings for cleaning options.', 'wc1c-main'),
		];

		$fields['clean'] =
		[
			'title' => __('Enable', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Check the box to enable this feature. Disabled by default.', 'wc1c-main'),
			'description' => __('All products received via the CommerceML protocol and matching keys for synchronization will be placed in the Recycle Bin.', 'wc1c-main'),
			'default' => 'no'
		];

		$fields['clean_final'] =
		[
			'title' => __('Final removal', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Check the box to enable this feature. Disabled by default.', 'wc1c-main'),
			'description' => __('All products received via the CommerceML protocol and matching keys for synchronization will be permanently deleted from the Recycle Bin.', 'wc1c-main'),
			'default' => 'no'
		];

		return $fields;
	}

	/**
	 * Configuration fields: logs
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function configurationsFieldsLogs($fields): array
	{
		$fields['title_logger'] =
		[
			'title' => __('Event logs', 'wc1c-main'),
			'type' => 'title',
			'description' => __('Maintaining event logs for the current configuration. You can view the logs through the extension or via FTP.', 'wc1c-main'),
		];

		$fields['logger_level'] =
		[
			'title' => __('Level for events', 'wc1c-main'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded in the log file. The higher the level, the less data is recorded.', 'wc1c-main'),
			'default' => '300',
			'options' =>
			[
				'logger_level' => __('Use level for main events', 'wc1c-main'),
				'100' => __('DEBUG (100)', 'wc1c-main'),
				'200' => __('INFO (200)', 'wc1c-main'),
				'250' => __('NOTICE (250)', 'wc1c-main'),
				'300' => __('WARNING (300)', 'wc1c-main'),
				'400' => __('ERROR (400)', 'wc1c-main'),
			],
		];

		$fields['logger_files_max'] =
		[
			'title' => __('Maximum files', 'wc1c-main'),
			'type' => 'text',
			'description' => __('Log files created daily. This option on the maximum number of stored files. By default saved of the logs are for the last 30 days.', 'wc1c-main'),
			'default' => 10,
			'css' => 'min-width: 20px;',
		];

		return $fields;
	}
}
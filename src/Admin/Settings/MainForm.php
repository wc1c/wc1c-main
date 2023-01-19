<?php namespace Wc1c\Main\Admin\Settings;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Settings\MainSettings;

/**
 * MainForm
 *
 * @package Wc1c\Main\Admin
 */
class MainForm extends Form
{
	/**
	 * MainForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->setId('settings-main');
		$this->setSettings(new MainSettings());

		add_filter($this->prefix . '_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_main'], 10);
		add_filter($this->prefix . '_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_configurations'], 20);
		add_filter($this->prefix . '_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_technical'], 30);

		$this->init();
	}

	/**
	 * Add fields for Configurations
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_configurations($fields): array
	{
		$fields['configurations_title'] =
		[
			'title' => __('Configurations', 'wc1c-main'),
			'type' => 'title',
			'description' => __('Some settings for the configurations.', 'wc1c-main'),
		];

		$fields['configurations_unique_name'] =
		[
			'title' => __('Unique names', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Require unique names for configurations?', 'wc1c-main'),
			'description' => __('If enabled, will need to provide unique names for the configurations.', 'wc1c-main'),
			'default' => 'yes'
		];

		$fields['configurations_show_per_page'] =
		[
			'title' => __('Number in the list', 'wc1c-main'),
			'type' => 'text',
			'description' => __('The number of displayed configurations on one page.', 'wc1c-main'),
			'default' => 10,
			'css' => 'min-width: 20px;',
		];

		$fields['configurations_draft_delete'] =
		[
			'title' => __('Deleting drafts without trash', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Enable deleting drafts without placing them in the trash?', 'wc1c-main'),
			'description' => __('If enabled, configurations for connections in the draft status will be deleted without being added to the basket.', 'wc1c-main'),
			'default' => 'yes'
		];

		return $fields;
	}

	/**
	 * Add for Technical
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_technical($fields): array
	{
		$fields['technical_title'] =
		[
			'title' => __('Technical settings', 'wc1c-main'),
			'type' => 'title',
			'description' => __('Used to set up the environment.', 'wc1c-main'),
		];

		$fields['php_max_execution_time'] =
		[
			'title' => __('Maximum time for execution PHP', 'wc1c-main'),
			'type' => 'text',
			'description' => sprintf
			(
				'%s <br /> %s <b>%s</b> <br /> %s',
				__('Value is seconds. WC1C will run until a time limit is set.', 'wc1c-main'),
				__('Server value:', 'wc1c-main'),
				wc1c()->environment()->get('php_max_execution_time'),
				__('If specify 0, the time limit will be disabled. Specifying 0 is not recommended, it is recommended not to exceed the server limit.', 'wc1c-main')
			),
			'default' => wc1c()->environment()->get('php_max_execution_time'),
			'css' => 'min-width: 100px;',
		];

		$fields['php_post_max_size'] =
        [
            'title' => __('Maximum request size', 'wc1c-main'),
            'type' => 'text',
            'description' => sprintf
            (
                '%s<br />%s <b>%s</b><hr>%s',
                __('Enter the maximum size of accepted requests at a time in bytes. May be specified with a dimension suffix, such as 7M, where M = megabyte, K = kilobyte, G - gigabyte.', 'wc1c-main'),
                __('Current SERVER limit:', 'wc1c-main'),
                wc1c()->environment()->get('php_post_max_size'),
                __('Can only decrease the value, because it must not exceed the limits from the server limit.', 'wc1c-main')
            ),
            'default' => wc1c()->environment()->get('php_post_max_size'),
            'css' => 'min-width: 100px;',
        ];

		return $fields;
	}

	/**
	 * Add for Main
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_main($fields): array
	{
		$fields['receiver'] =
		[
			'title' => __('Receiver', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Enable data Receiver: background requests?', 'wc1c-main'),
			'description' => __('It is used to receive background requests from 1C in exchange schemes. Do not disable this option if you do not know what it is for.', 'wc1c-main'),
			'default' => 'yes'
		];

		return $fields;
	}
}
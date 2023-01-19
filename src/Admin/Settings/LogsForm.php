<?php namespace Wc1c\Main\Admin\Settings;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Settings\LogsSettings;

/**
 * LogsForm
 *
 * @package Wc1c\Main\Admin
 */
class LogsForm extends Form
{
	/**
	 * LogsForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->setId('settings-logs');
		$this->setSettings(new LogsSettings());

		add_filter('wc1c_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_logger'], 10);

		$this->init();
	}

	/**
	 * Add settings for logger
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_logger($fields)
	{
		$fields['logger_level'] =
		[
			'title' => __('Level for main events', 'wc1c-main'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded in the log file. The higher the level, the less data is recorded.', 'wc1c-main'),
			'default' => '300',
			'options' =>
			[
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
			'default' => 30,
			'css' => 'min-width: 20px;',
		];

		$fields['logger_title_level'] =
		[
			'title' => __('Levels by context', 'wc1c-main'),
			'type' => 'title',
			'description' => __('Event log settings based on context.', 'wc1c-main'),
		];

		$fields['logger_receiver_level'] =
		[
			'title' => __('Receiver', 'wc1c-main'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded the Receiver events in the log file. The higher the level, the less data is recorded.', 'wc1c-main'),
			'default' => 'logger_level',
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

		$fields['logger_tools_level'] =
		[
			'title' => __('Tools', 'wc1c-main'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded the tools events in the log file. The higher the level, the less data is recorded.', 'wc1c-main'),
			'default' => 'logger_level',
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

		$fields['logger_schemas_level'] =
		[
			'title' => __('Schemas', 'wc1c-main'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded the schemas events in the log file. The higher the level, the less data is recorded.', 'wc1c-main'),
			'default' => 'logger_level',
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

		$fields['logger_configurations_level'] =
		[
			'title' => __('Configurations', 'wc1c-main'),
			'type' => 'select',
			'description' => __('All events of the selected level will be recorded the configurations events in the log file. The higher the level, the less data is recorded.', 'wc1c-main'),
			'default' => 'logger_level',
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

		return $fields;
	}
}
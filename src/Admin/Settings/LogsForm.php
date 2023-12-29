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
	 * @param array $fields
	 *
	 * @return array
	 */
	public function init_fields_logger(array $fields): array
	{
		$fields['logger_level'] =
		[
			'title' => __('Level for main events', 'wc1c-main'),
			'type' => 'select',
			'description' => sprintf
            (
                '%s %s<hr><b>%s</b>: %s<br/><b>%s</b>: %s<br/><b>%s</b>: %s<br/><b>%s</b>: %s<br/><b>%s</b>: %s<hr>%s',
                __('Events of the selected level will be recorded in the log file. The bigger the level (numbers), the less data is recorded.', 'wc1c-main'),
                __('If the plugin is stable, you will need to use at least the NOTICE level (250), otherwise the event logs will become very large.', 'wc1c-main'),
                __('DEBUG (100)', 'wc1c-main'),
                __('Data from the algorithm development process with debugging information. It is used by administrators in case of some unclear errors in the process of plugin operation.', 'wc1c-main'),
                __('INFO (200)', 'wc1c-main'),
                __('Data from the algorithm development process, but without debugging information. In this mode, less data is written than in debugging mode, but still a lot.', 'wc1c-main'),
                __('NOTICE (250)', 'wc1c-main'),
                __('This mode records events that notify of situations that you need to be aware of. For example, when changing settings or renaming a product during data exchange.', 'wc1c-main'),
                __('WARNING (300)', 'wc1c-main'),
                __('Recording warnings that occur during operation. Warnings are worth investigating and taking into account, but they are not errors.', 'wc1c-main'),
                __('ERROR (400)', 'wc1c-main'),
                __('Will be recorded only data on errors occurring during the processing of plugin algorithms.', 'wc1c-main'),
                __('Event logs always record data about critical errors in the code, regardless of the level configured.', 'wc1c-main')
            ),
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

		$fields['logger_output'] =
		[
			'title' => __('Output on display', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Check the box if you want to enable this feature. Disabled by default.', 'wc1c-main'),
			'description' => sprintf
            (
                '%s<hr>%s',
                __('All entries event logs will be displayed on the screen.', 'wc1c-main'),
                __('It is used only by developers. If enable this setting just for fun, 1C will not be able to connect to the site.', 'wc1c-main')
            ),
			'default' => 'no'
		];

		$fields['logger_title_level'] =
		[
			'title' => __('Levels by context', 'wc1c-main'),
			'type' => 'title',
            'description' => sprintf
            (
                '%s %s',
                __('Event log settings based on context.', 'wc1c-main'),
                __('Logical distribution of event logs into contexts for flexible analysis.', 'wc1c-main')
            ),
		];

		$fields['logger_receiver_level'] =
		[
			'title' => __('Receiver', 'wc1c-main'),
			'type' => 'select',
            'description' => sprintf
            (
                '%s<hr>%s',
                __('All events of the selected level will be recorded the Receiver events in the log file. The higher the level, the less data is recorded.', 'wc1c-main'),
                __('It is convenient to use for debugging missing requests from 1C to the site in case need to understand on which side there is a problem with the connection.', 'wc1c-main')
            ),
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
			'description' => sprintf
            (
                '%s<hr>%s',
                __('All events of the selected level are recorded in the tools events log file. The higher the level, the less data is logged.', 'wc1c-main'),
                __('In this context, events are recorded when users work with tools, both global and for a specific configuration.', 'wc1c-main')
            ),
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
            'description' => sprintf
            (
                '%s<hr>%s',
                __('All events of the selected level are recorded in the events log files for schemas. The higher the level, the less data is logged.', 'wc1c-main'),
                __('Only events that are processed in the schema algorithms are recorded. If the events are related to the user configuration, they are not written to schema events.', 'wc1c-main')
            ),
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
			'description' => sprintf
            (
                '%s<hr>%s',
                __('All events of the selected level will be recorded the configurations events in the log file. The higher the level, the less data is recorded.', 'wc1c-main'),
                __('A personalized event log level can be set on the level of each configuration. The current value is taken for creating new configurations.', 'wc1c-main')
            ),
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
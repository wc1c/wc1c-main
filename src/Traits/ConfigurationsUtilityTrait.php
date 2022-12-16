<?php namespace Wc1c\Main\Traits;

defined('ABSPATH') || exit;

/**
 * ConfigurationsUtilityTrait
 *
 * @package Wc1c\Main\Traits
 */
trait ConfigurationsUtilityTrait
{
	/**
	 * Get all available configurations statuses
	 *
	 * @return array
	 */
	public function utilityConfigurationsGetStatuses()
	{
		$statuses =
		[
			'draft',
			'inactive',
			'active',
			'processing',
			'error',
			'deleted',
		];

		return apply_filters('wc1c_configurations_get_statuses', $statuses);
	}

	/**
	 * Get normal configuration status
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	public function utilityConfigurationsGetStatusesLabel($status)
	{
		$default_label = __('Undefined', 'wc1c-main');

		$statuses_labels = apply_filters
		(
			'wc1c_configurations_get_statuses_labels',
			[
				'draft' => __('Draft', 'wc1c-main'),
				'active' => __('Active', 'wc1c-main'),
				'inactive' => __('Inactive', 'wc1c-main'),
				'error' => __('Error', 'wc1c-main'),
				'processing' => __('Processing', 'wc1c-main'),
				'deleted' => __('Deleted', 'wc1c-main'),
			]
		);

		if(empty($status) || !array_key_exists($status, $statuses_labels))
		{
			$status_label = $default_label;
		}
		else
		{
			$status_label = $statuses_labels[$status];
		}

		return apply_filters('wc1c_configurations_get_statuses_label_return', $status_label, $status, $statuses_labels);
	}

	/**
	 * Get folder name for configuration statuses
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	public function utilityConfigurationsGetStatusesFolder($status)
	{
		$default_folder = __('Undefined', 'wc1c-main');

		$statuses_folders = apply_filters
		(
			'wc1c_configurations_get_statuses_folders',
			[
				'draft' => __('Drafts', 'wc1c-main'),
				'active' => __('Activated', 'wc1c-main'),
				'inactive' => __('Deactivated', 'wc1c-main'),
				'error' => __('With errors', 'wc1c-main'),
				'processing' => __('In processing', 'wc1c-main'),
				'deleted' => __('Trash', 'wc1c-main'),
			]
		);

		$status_folder = $default_folder;

		if(!empty($status) || array_key_exists($status, $statuses_folders))
		{
			$status_folder = $statuses_folders[$status];
		}

		return apply_filters('wc1c_configurations_get_statuses_folder_return', $status_folder, $status, $statuses_folders);
	}
}
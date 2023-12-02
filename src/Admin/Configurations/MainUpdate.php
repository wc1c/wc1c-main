<?php namespace Wc1c\Main\Admin\Configurations;

defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Traits\ProcessConfigurationTrait;
use Wc1c\Main\Exceptions\RuntimeException;
use Wc1c\Main\Traits\ConfigurationsUtilityTrait;
use Wc1c\Main\Traits\DatetimeUtilityTrait;
use Wc1c\Main\Traits\SectionsTrait;
use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Traits\UtilityTrait;

/**
 * MainUpdate
 *
 * @package Wc1c\Main\Admin
 */
class MainUpdate
{
	use SingletonTrait;
	use DatetimeUtilityTrait;
	use UtilityTrait;
	use SectionsTrait;
	use ProcessConfigurationTrait;
	use ConfigurationsUtilityTrait;

	/**
	 * Update processing
	 */
	public function process()
	{
		$configuration = $this->getConfiguration();
		$form = new UpdateForm();

		$form_data = $configuration->getOptions();

		$form_data['status'] = $configuration->isEnabled() ? 'yes' : 'no';
        $form_data['status'] = $configuration->isDraft() ? 'yes' : $form_data['status'];

		$form->loadSavedData($form_data);

		if(isset($_GET['form']) && sanitize_text_field($_GET['form']) === $form->getId())
		{
			$data = $form->save();

			if($data)
			{
				// Галка стоит
				if($data['status'] === 'yes')
				{
					if($configuration->isEnabled() === false)
					{
						$configuration->setStatus('active');
					}
				}
				// галка не стоит
				else
				{
					$configuration->setStatus('inactive');
				}
				unset($data['status']);

				$configuration->setDateModify(time());
				$configuration->setOptions($data);

				$saved = $configuration->save();

				if($saved)
				{
					wc1c()->admin()->notices()->create
					(
						[
							'type' => 'update',
							'data' => __('Configuration update success.', 'wc1c-main')
						]
					);
				}
				else
				{
					wc1c()->admin()->notices()->create
					(
						[
							'type' => 'error',
							'data' => __('Configuration update error. Please retry saving or change fields.', 'wc1c-main')
						]
					);
				}
			}
		}

		add_action('wc1c_admin_configurations_update_sidebar_show', [$this, 'outputSidebar'], 10);
		add_action('wc1c_admin_configurations_update_show', [$form, 'output'], 10);
	}

	/**
	 * Sidebar show
	 */
	public function outputSidebar()
	{
		$configuration = $this->getConfiguration();

		$configuration_options = $configuration->getOptions();
		if(isset($configuration_options['logger_level']))
		{
            $args = [];

			if((int)$configuration_options['logger_level'] === 100)
			{
				$args =
				[
					'type' => 'danger',
					'header' => '<h4 class="alert-heading mt-0 mb-1">' . __('Debug is enabled!', 'wc1c-main') . '</h4>',
					'object' => $this,
					'body' => __('The current configuration has debug mode enabled. You must disable this mode after debugging is complete.', 'wc1c-main')
				];
			}

			if((int)$configuration_options['logger_level'] === 200)
			{
				$args =
				[
					'type' => 'warning',
					'header' => '<h4 class="alert-heading mt-0 mb-1">' . __('Info is enabled!', 'wc1c-main') . '</h4>',
					'object' => $this,
					'body' => __('The extended information recording mode is enabled for the current configuration. It is recommended to disable this mode after debugging is complete.', 'wc1c-main')
				];
			}

			if((int)$configuration_options['logger_level'] <= 200)
			{
				wc1c()->views()->getView('configurations/update_sidebar_alert_item.php', $args);
			}
		}

		$args =
		[
			'header' => '<h3 class="p-0 m-0">' . __('About configuration', 'wc1c-main') . '</h3>',
			'object' => $this
		];

		$body = '<ul class="list-group m-0 list-group-flush">';
		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('ID:', 'wc1c-main') . ' <b>' . $configuration->getId() . '</b>';
		$body .= '</li>';
		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Schema ID:', 'wc1c-main') . ' <b>' . $configuration->getSchema() . '</b>';
		$body .= '</li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Status', 'wc1c-main') . ': <b>' . $this->utilityConfigurationsGetStatusesLabel($configuration->getStatus()) . '</b>';
		$body .= '</li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$user_id = $configuration->getUserId();
		$user = get_userdata($user_id);
		if($user instanceof \WP_User && $user->exists())
		{
			$body .= __('Owner:', 'wc1c-main') . ' <b>' . $user->get('nickname') . '</b> (' . $user_id. ')';
		}
		else
		{
			$body .= __('User is not exists.', 'wc1c-main');
		}
		$body .= '</li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Date active:', 'wc1c-main') . '<div class="p-1 mt-1 bg-light">' . $this->utilityPrettyDate($configuration->getDateActivity());

		if($configuration->getDateActivity())
		{
			$body .= sprintf(_x(' (%s ago).', '%s = human-readable time difference', 'wc1c-main'), human_time_diff($configuration->getDateActivity()->getOffsetTimestamp(), current_time('timestamp')));
		}

		$body .= '</div></li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Date create:', 'wc1c-main') . '<div class="p-1 mt-1 bg-light">' . $this->utilityPrettyDate($configuration->getDateCreate());

		if($configuration->getDateCreate())
		{
			$body .= sprintf(_x(' (%s ago).', '%s = human-readable time difference', 'wc1c-main'), human_time_diff($configuration->getDateCreate()->getOffsetTimestamp(), current_time('timestamp')));
		}

		$body .= '</div></li>';
		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Date modify:', 'wc1c-main') . '<div class="p-1 mt-1 bg-light">'. $this->utilityPrettyDate($configuration->getDateModify());

		if($configuration->getDateModify())
		{
			$body .= sprintf(_x(' (%s ago).', '%s = human-readable time difference', 'wc1c-main'), human_time_diff($configuration->getDateModify()->getOffsetTimestamp(), current_time('timestamp')));
		}

		$body .= '</div></li>';

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Directory:', 'wc1c-main') . '<div class="p-1 mt-1 bg-light">' . wp_normalize_path($configuration->getUploadDirectory()) . '</div>';
		$body .= '</li>';

		$size = 0;
		$files = wc1c()->filesystem()->files($configuration->getUploadDirectory() . '/catalog');

		foreach($files as $file)
		{
			$size += wc1c()->filesystem()->size($file);
		}

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Directory size:', 'wc1c-main') . ' <b>' . size_format($size) . '</b>';
		$body .= '</li>';

		$size = 0;
		$files = wc1c()->filesystem()->files($configuration->getUploadDirectory('logs'));

		foreach($files as $file)
		{
			$size += wc1c()->filesystem()->size($file);
		}

		$body .= '<li class="list-group-item p-2 m-0">';
		$body .= __('Logs directory size:', 'wc1c-main') . ' <b>' . size_format($size) . '</b>';
		$body .= '</li>';

		$body .= '</ul>';

		$args['body'] = $body;

		wc1c()->views()->getView('configurations/update_sidebar_item.php', $args);

		try
		{
			$schema = wc1c()->schemas()->get($configuration->getSchema());

			$args =
			[
				'header' => '<h3 class="p-0 m-0">' . __('About schema', 'wc1c-main') . '</h3>',
				'object' => $this
			];

			$body = '<ul class="list-group m-0 list-group-flush">';
			$body .= '<li class="list-group-item p-2 m-0">';
			$body .= $schema->getDescription();
			$body .= '</li>';

			$body .= '</ul>';

			$args['body'] = $body;

			wc1c()->views()->getView('configurations/update_sidebar_item.php', $args);
		}
		catch(RuntimeException $e){}
	}
}
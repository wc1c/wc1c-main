<?php namespace Wc1c\Main\Admin\Configurations;

defined('ABSPATH') || exit;

use Wc1c\Main\Admin\InlineForm;
use Wc1c\Main\Admin\Traits\ProcessConfigurationTrait;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Traits\DatetimeUtilityTrait;
use Wc1c\Main\Traits\SectionsTrait;
use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Traits\UtilityTrait;

/**
 * Update
 *
 * @package Wc1c\Main\Admin
 */
class Update
{
	use SingletonTrait;
	use ProcessConfigurationTrait;
	use DatetimeUtilityTrait;
	use UtilityTrait;
	use SectionsTrait;

	/**
	 * Update constructor.
	 */
	public function __construct()
	{
		$this->setSectionKey('update_section');

		$default_sections['main'] =
		[
			'title' => __('Settings', 'wc1c-main'),
			'visible' => true,
			'callback' => [MainUpdate::class, 'instance']
		];

        $default_sections['logs'] =
        [
            'title' => __('Logs', 'wc1c-main'),
            'visible' => true,
            'callback' => [\Wc1c\Main\Admin\Promo\Logs::class, 'instance'],
            'class' => 'promo'
        ];

		if(has_action('wc1c_admin_configurations_update_sections'))
		{
			$default_sections = apply_filters('wc1c_admin_configurations_update_sections', $default_sections);
		}

		$this->initSections($default_sections);
		$this->setCurrentSection('main');

		$configuration_id = absint(wc1c()->getVar($_GET['configuration_id'], 0));

		if(false === $this->setConfiguration($configuration_id))
		{
			try
			{
				wc1c()->schemas()->init($this->getConfiguration());
			}
			catch(Exception $e)
			{
				add_action('wc1c_admin_configurations_update_show', [$this, 'outputSchemaError'], 10);
				add_filter('wc1c_admin_configurations_update_schema_error_text', [$this, 'outputSchemaErrorText'], 10, 1);

				wc1c()->log()->notice('Schema is not initialized.', ['exception' => $e]);
			}

			$this->process();
		}
		else
		{
			add_action('wc1c_admin_show', [$this, 'outputError'], 10);
			wc1c()->log()->notice('Configuration update is not available.', ['configuration_id' => $configuration_id]);
			return;
		}

		$this->route();

		add_action('wc1c_admin_show', [$this, 'output'], 10);
	}

	/**
	 * @param $text
	 *
	 * @return string
	 */
	public function outputSchemaErrorText($text): string
	{
		$new_text = __('The exchange schema based on which the configuration was created is not available.', 'wc1c-main');

		$new_text .= '<br /> ' . __('Install the missing schema to work this configuration, change the status and name or delete the configuration.', 'wc1c-main');

		return $new_text;
	}

	/**
	 *  Routing
	 */
	public function route()
	{
		$sections = $this->getSections();
		$current_section = $this->initCurrentSection();

		if(!array_key_exists($current_section, $sections) || !isset($sections[$current_section]['callback']))
		{
			add_action('wc1c_admin_configurations_update_show', [$this, 'wrapError']);
		}
		else
		{
			add_action('wc1c_admin_before_configurations_update_show', [$this, 'wrapSections'], 5);

			$callback = $sections[$current_section]['callback'];

			if(is_callable($callback, false, $callback_name))
			{
				$callback_obj = $callback_name();
				$callback_obj->setConfiguration($this->getConfiguration());
				$callback_obj->process();
			}
		}
	}

	/**
	 * Update processing
	 */
	public function process()
	{
		$configuration = $this->getConfiguration();

		$fields['name'] =
		[
			'title' => __('Configuration name', 'wc1c-main'),
			'type' => 'text',
			'description' => __('Used for convenient distribution of multiple configurations.', 'wc1c-main'),
			'default' => '',
			'class' => 'form-control form-control-sm rounded-0',
            'button_class' => 'rounded-0',
			'button' => __('Rename', 'wc1c-main'),
		];

		$inline_args =
		[
			'id' => 'configurations-name',
			'fields' => $fields
		];

		$inline_form = new InlineForm($inline_args);
		$inline_form->loadSavedData(['name' => $configuration->getName()]);

		if(isset($_GET['form']) && sanitize_text_field($_GET['form']) === $inline_form->getId())
		{
			$configuration_name = $inline_form->save();

			if(isset($configuration_name['name']))
			{
				$configuration->setDateModify(time());
				$configuration->setName($configuration_name['name']);

				$saved = $configuration->save();

				if($saved)
				{
					wc1c()->admin()->notices()->create
					(
						[
							'type' => 'update',
							'data' => __('Configuration name update success.', 'wc1c-main')
						]
					);
				}
				else
				{
					wc1c()->admin()->notices()->create
					(
						[
							'type' => 'error',
							'data' => __('Configuration name update error. Please retry saving or change fields.', 'wc1c-main')
						]
					);
				}
			}
		}

		add_action('wc1c_admin_configurations_update_header_show', [$inline_form, 'output'], 10);
	}

	/**
	 * Error
	 */
	public function wrapError()
	{
		wc1c()->views()->getView('error.php');
	}

	/**
	 * Output error
	 */
	public function outputError()
	{
		$args['back_url'] = $this->utilityAdminConfigurationsGetUrl('all');

		wc1c()->views()->getView('configurations/update_error.php', $args);
	}

	/**
	 * Output schema error
	 */
	public function outputSchemaError()
	{
		wc1c()->views()->getView('configurations/update_schema_error.php');
	}

	/**
	 * Sections
	 *
	 * @return void
	 */
	public function wrapSections()
	{
		$args['object'] = $this;

		wc1c()->views()->getView('configurations/update_sections.php', $args);
	}

	/**
	 * Output
	 *
	 * @return void
	 */
	public function output()
	{
		$args['back_url'] = $this->utilityAdminConfigurationsGetUrl('all');

		wc1c()->views()->getView('configurations/update.php', $args);
	}
}
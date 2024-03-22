<?php namespace Wc1c\Main\Admin\Settings;

defined('ABSPATH') || exit;

use Wc1c\Main\Abstracts\FormAbstract;
use Wc1c\Main\Abstracts\SettingsAbstract;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Traits\SingletonTrait;

/**
 * Form
 *
 * @package Wc1c\Main\Admin
 */
abstract class Form extends FormAbstract
{
	use SingletonTrait;

	/**
	 * @var SettingsAbstract
	 */
	public $settings;

	/**
	 * @return SettingsAbstract
	 */
	public function getSettings(): SettingsAbstract
	{
		return $this->settings;
	}

	/**
	 * @param SettingsAbstract $settings
	 */
	public function setSettings(SettingsAbstract $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Lazy load
	 *
	 * @throws Exception
	 */
	protected function init()
	{
		$this->loadFields();
		$this->getSettings()->init();
		$this->loadSavedData($this->getSettings()->get());
		$this->save();

		add_action('wc1c_admin_show', [$this, 'outputForm']);
	}

	/**
	 * Save
	 *
	 * @return bool
	 */
	public function save()
	{
		$post_data = $this->getPostedData();

		if(!isset($post_data['_wc1c-admin-nonce']))
		{
			return false;
		}

        wc1c()->log()->info(__('Saving settings.', 'wc1c-main'));

        $message = __('The settings have not been saved.', 'wc1c-main');

        if(empty($post_data) || !wp_verify_nonce($post_data['_wc1c-admin-nonce'], 'wc1c-admin-settings-save'))
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => $message
				]
			);

            wc1c()->log()->warning($message, ['user_id' => get_current_user_id(), 'form_id' => $this->getId()]);

			return false;
		}

		/**
		 * All form fields validate
		 */
		foreach($this->getFields() as $key => $field)
		{
			if('title' === $this->getFieldType($field))
			{
				continue;
			}

			try
			{
				$this->saved_data[$key] = $this->getFieldValue($key, $field, $post_data);
			}
			catch(\Throwable $e)
			{
				wc1c()->admin()->notices()->create
				(
					[
						'type' => 'error',
						'data' => $e->getMessage()
					]
				);

                wc1c()->log()->error($message, ['user_id' => get_current_user_id(), 'exception' => $e, 'form_id' => $this->getId()]);
			}
		}

        $saved_data = $this->getSavedData();

        try
		{
			$this->getSettings()->set($saved_data);
			$this->getSettings()->save();
		}
		catch(\Throwable $e)
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => $e->getMessage()
				]
			);

            wc1c()->log()->warning($message, ['user_id' => get_current_user_id(), 'exception' => $e, 'form_id' => $this->getId()]);

			return false;
		}

        $message = __('The settings have been successfully saved.', 'wc1c-main');

		wc1c()->admin()->notices()->create
		(
			[
				'type' => 'update',
				'data' => $message
			]
		);

        wc1c()->log()->notice($message, ['user_id' => get_current_user_id(), 'data' => $saved_data, 'form_id' => $this->getId()]);

		return true;
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

		wc1c()->views()->getView('settings/form.php', $args);
	}
}
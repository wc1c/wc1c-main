<?php namespace Wc1c\Main\Admin\Settings;

defined('ABSPATH') || exit;

use Wc1c\Main\Abstracts\FormAbstract;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Settings\Contracts\SettingsContract;
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
	 * @var SettingsContract
	 */
	public $settings;

	/**
	 * @return SettingsContract
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * @param SettingsContract $settings
	 */
	public function setSettings($settings)
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
		$this->load_fields();
		$this->getSettings()->init();
		$this->load_saved_data($this->getSettings()->get());
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
		$post_data = $this->get_posted_data();

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
					'data' => __('Save error. Please retry.', 'wc1c-main')
				]
			);

			return false;
		}

		/**
		 * All form fields validate
		 */
		foreach($this->get_fields() as $key => $field)
		{
			if('title' === $this->get_field_type($field))
			{
				continue;
			}

			try
			{
				$this->saved_data[$key] = $this->get_field_value($key, $field, $post_data);
			}
			catch(Exception $e)
			{
				wc1c()->admin()->notices()->create
				(
					[
						'type' => 'error',
						'data' => $e->getMessage()
					]
				);
			}
		}

		try
		{
			$this->getSettings()->set($this->get_saved_data());
			$this->getSettings()->save();
		}
		catch(Exception $e)
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => $e->getMessage()
				]
			);

			return false;
		}

		wc1c()->admin()->notices()->create
		(
			[
				'type' => 'update',
				'data' => __('Save success.', 'wc1c-main')
			]
		);

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
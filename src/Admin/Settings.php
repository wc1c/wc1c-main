<?php namespace Wc1c\Main\Admin;

defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Settings\ActivationForm;
use Wc1c\Main\Admin\Settings\ConnectionForm;
use Wc1c\Main\Admin\Settings\LogsForm;
use Wc1c\Main\Admin\Settings\MainForm;
use Wc1c\Main\Admin\Settings\InterfaceForm;
use Wc1c\Main\Traits\SectionsTrait;
use Wc1c\Main\Traits\SingletonTrait;

/**
 * Settings
 *
 * @package Wc1c\Main\Admin
 */
class Settings
{
	use SingletonTrait;
	use SectionsTrait;

	/**
	 * Settings constructor.
	 */
	public function __construct()
	{
		// hook
		do_action('wc1c_admin_settings_before_loading');

		$this->init();
		$this->route();

		// hook
		do_action('wc1c_admin_settings_after_loading');
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		// hook
		do_action('wc1c_admin_settings_before_init');

		$default_sections['main'] =
		[
			'title' => __('Main', 'wc1c-main'),
			'visible' => true,
			'callback' => [MainForm::class, 'instance']
		];

		$default_sections['activation'] =
		[
			'title' => __('Activation', 'wc1c-main'),
			'visible' => true,
			'callback' => [ActivationForm::class, 'instance']
		];

		$default_sections['logs'] =
		[
			'title' => __('Event logs', 'wc1c-main'),
			'visible' => true,
			'callback' => [LogsForm::class, 'instance']
		];

		$default_sections['interface'] =
		[
			'title' => __('Interface', 'wc1c-main'),
			'visible' => true,
			'callback' => [InterfaceForm::class, 'instance']
		];

		$default_sections['connection'] =
		[
			'title' => __('Connection to the WC1C', 'wc1c-main'),
			'visible' => true,
			'callback' => [ConnectionForm::class, 'instance']
		];

		$this->initSections($default_sections);

		// hook
		do_action('wc1c_admin_settings_after_init');
	}

	/**
	 * Initializing current section
	 *
	 * @return string
	 */
	public function initCurrentSection(): string
	{
		$current_section = !empty($_GET['do_settings']) ? sanitize_title($_GET['do_settings']) : 'main';

		if($current_section !== '')
		{
			$this->setCurrentSection($current_section);
		}

		return $this->getCurrentSection();
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
			add_action('wc1c_admin_show', [$this, 'wrapError']);
		}
		else
		{
			add_action('wc1c_admin_show', [$this, 'wrapSections'], 7);

			$callback = $sections[$current_section]['callback'];

			if(is_callable($callback, false, $callback_name))
			{
				$callback_name();
			}
		}
	}

	/**
	 * Sections
	 */
	public function wrapSections()
	{
		wc1c()->views()->getView('settings/sections.php');
	}

	/**
	 * Error
	 */
	public function wrapError()
	{
		wc1c()->views()->getView('settings/error.php');
	}
}
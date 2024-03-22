<?php namespace Wc1c\Main;

defined('ABSPATH') || exit;

use Digiom\Wotices\Interfaces\ManagerInterface;
use Digiom\Wotices\Manager;
use Wc1c\Main\Admin\Configurations;
use Wc1c\Main\Admin\Extensions;
use Wc1c\Main\Admin\Settings;
use Wc1c\Main\Admin\Tools;
use Wc1c\Main\Traits\SectionsTrait;
use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Traits\UtilityTrait;

/**
 * Admin
 *
 * @package Wc1c\Main
 */
final class Admin
{
	use SingletonTrait;
	use SectionsTrait;
	use UtilityTrait;

	/**
	 * @var ManagerInterface Admin notices
	 */
	private $notices;

	/**
	 * Admin constructor.
	 */
	public function __construct()
	{
		// hook
		do_action(wc1c()->context()->getSlug() . '_admin_before_loading');

		$this->notices();

		if('yes' === wc1c()->settings('interface')->get('admin_interface', 'yes'))
		{
			Admin\Columns\Init::instance();
			Admin\Metaboxes\Init::instance();
		}

		add_action('admin_menu', [$this, 'addMenu'], 30);

		if(isset($_GET['page']) && 'wc1c' === $_GET['page'] && wc1c()->context()->isAdmin())
		{
			add_action('init', [$this, 'init'], 10);
			add_action('admin_enqueue_scripts', [$this, 'initStyles']);
			add_action('admin_enqueue_scripts', [$this, 'initScripts']);

			Admin\Helps\Init::instance();
			Admin\Wizards\Init::instance();
		}

		if(function_exists('is_plugin_active') && is_plugin_active('wc1c/wc1c.php'))
		{
			deactivate_plugins( 'wc1c/wc1c.php', true);
		}

		add_filter('plugin_action_links_' . wc1c()->environment()->get('plugin_basename'), [$this, 'linksLeft']);

		// hook
		do_action(wc1c()->context()->getSlug() . '_admin_after_loading');
	}

	/**
	 * Admin notices
	 *
	 * @return ManagerInterface
	 */
	public function notices()
	{
		if(empty($this->notices))
		{
            $admin = isset($_GET['page']) && 'wc1c' === $_GET['page'] && wc1c()->context()->isAdmin();

			$args =
			[
				'auto_save' => true,
				'admin_notices' => !$admin,
				'user_admin_notices' => false,
				'network_admin_notices' => false
			];

			$this->notices = new Manager(wc1c()->context()->getSlug() . '_admin_notices', $args);
		}

		return $this->notices;
	}

	/**
	 * Init menu
	 */
	public function addMenu()
	{
		add_submenu_page
		(
			'woocommerce',
			__('Integration with 1C', 'wc1c-main'),
			__('Integration with 1C', 'wc1c-main'),
			'manage_woocommerce',
            wc1c()->context()->getSlug(),
			[$this, 'route']
		);
	}

	/**
	 * Initialization
	 */
	public function init()
	{
		// hook
		do_action(wc1c()->context()->getSlug() . '_admin_before_init');

		$default_sections['configurations'] =
		[
			'title' => __('Configurations', 'wc1c-main'),
			'visible' => true,
			'callback' => [Configurations::class, 'instance']
		];

		$default_sections['tools'] =
		[
			'title' => __('Tools', 'wc1c-main'),
			'visible' => true,
			'callback' => [Tools::class, 'instance']
		];

		if(current_user_can('manage_options'))
		{
			$default_sections['settings'] =
			[
				'title' => __('Settings', 'wc1c-main'),
				'visible' => true,
				'callback' => [Settings::class, 'instance']
			];
		}

		if(current_user_can('edit_plugins') || current_user_can('install_plugins') || current_user_can('update_plugins'))
		{
			$default_sections['extensions'] =
			[
				'title' => __('Extensions', 'wc1c-main'),
				'visible' => true,
				'callback' => [Extensions::class, 'instance']
			];
		}

		if(!wc1c()->tecodes()->is_valid())
		{
			$default_sections['promo'] =
			[
				'title' => __('Activation', 'wc1c-main'),
				'visible' => true,
				'callback' => [Admin\Promo\Activation::class, 'instance'],
				'class' => 'promo'
			];
		}

		$this->initSections($default_sections);
		$this->setCurrentSection('configurations');

		// hook
		do_action(wc1c()->context()->getSlug() . '_admin_after_init');
	}

	/**
	 * Styles
	 */
	public function initStyles()
	{
		wp_enqueue_style
		(
			'wc1c_admin_main',
			wc1c()->environment()->get('plugin_directory_url') . 'assets/css/main.min.css',
			[],
			wc1c()->environment()->get('wc1c_version')
		);
	}

	/**
	 * Scripts
	 */
	public function initScripts()
	{
        wp_enqueue_script
        (
			'wc1c_admin_bootstrap',
			wc1c()->environment()->get('plugin_directory_url') . 'assets/js/bootstrap.bundle.min.js',
			[],
			wc1c()->environment()->get('wc1c_version')
        );

        wp_enqueue_script
        (
			'wc1c_admin_tocbot',
			wc1c()->environment()->get('plugin_directory_url') . 'assets/js/tocbot/tocbot.min.js',
			[],
			wc1c()->environment()->get('wc1c_version')
        );

		wp_enqueue_script
		(
			'wc1c_admin_main',
			wc1c()->environment()->get('plugin_directory_url') . 'assets/js/admin.js',
			[],
			wc1c()->environment()->get('wc1c_version')
		);
	}

	/**
	 * Route sections
	 */
	public function route()
	{
		$sections = $this->getSections();
		$current_section = $this->initCurrentSection();

		if(!array_key_exists($current_section, $sections) || !isset($sections[$current_section]['callback']))
		{
			add_action(wc1c()->context()->getSlug() . '_admin_show', [$this, 'wrapError']);
		}
		else
		{
			if(false === get_option('wc1c_wizard', false))
			{
				add_action(wc1c()->context()->getSlug() . '_admin_show', [$this, 'wrapHeader'], 3);
			}

			add_action(wc1c()->context()->getSlug() . '_admin_show', [$this, 'wrapSections'], 7);

			$callback = $sections[$current_section]['callback'];

			if(is_callable($callback, false, $callback_name))
			{
				$callback_name();
			}
		}

		wc1c()->views()->getView('wrap.php');
	}

	/**
	 * Error
	 */
	public function wrapError()
	{
		wc1c()->views()->getView('error.php');
	}

	/**
	 * Header
	 */
	public function wrapHeader()
	{
		$args['url_create'] = $this->utilityAdminConfigurationsGetUrl('create');

		wc1c()->views()->getView('header.php', $args);
	}

	/**
	 * Sections
	 */
	public function wrapSections()
	{
		wc1c()->views()->getView('sections.php');
	}

	/**
	 * Setup left links
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function linksLeft(array $links): array
	{
		return array_merge(['site' => '<a href="' . esc_url(admin_url('admin.php?page=wc1c')) . '">' . __('Dashboard', 'wc1c-main') . '</a>'], $links);
	}

	/**
	 * Connect box
	 *
	 * @param string $text Button text
	 * @param bool $status
	 */
	public function connectBox(string $text, bool $status = false)
	{
		$class = 'page-title-action nav-connect';
		if($status === false)
		{
			$class .= ' status-0';
		}
		else
		{
			$class .= ' status-1';
		}

		if(wc1c()->tecodes()->is_valid() && $status)
		{
			$local = wc1c()->tecodes()->get_local_code();
			$local_data = wc1c()->tecodes()->get_local_code_data($local);

			if($local_data['code_date_expires'] === 'never')
			{
				$local_data['code_date_expires'] = __('never', 'wc1c-main');
				$text .= ' (' . __('no deadline', 'wc1c-main') . ')';
			}
			else
			{
				$local_data['code_date_expires'] = date_i18n(get_option('date_format'), $local_data['code_date_expires']);
				$text .= ' (' . __('to:', 'wc1c-main') . ' ' . $local_data['code_date_expires'] . ')';
			}

			$class .= ' status-3';
		}
		elseif($status)
		{
			$text .= ' (' . __('not activated', 'wc1c-main') . ')';
			$class .= ' status-2';
		}

		echo wp_kses_post('<a href="' . admin_url('admin.php?page=wc1c&section=settings&do_settings=connection') . '" class="' . esc_attr($class) . '"> ' . sanitize_text_field($text) . ' </a>');
	}
}
<?php namespace Wc1c\Main\Admin\Helps;

defined('ABSPATH') || exit;

use Wc1c\Main\Traits\SingletonTrait;

/**
 * Init
 *
 * @package Wc1c\Main\Admin
 */
final class Init
{
	use SingletonTrait;

	/**
	 * Init constructor.
	 */
	public function __construct()
	{
		add_action('current_screen', [$this, 'add_tabs'], 50);
	}

	/**
	 * Add help tabs
	 */
	public function add_tabs()
	{
		$screen = get_current_screen();

		if(!$screen)
		{
			return;
		}

		$screen->add_help_tab
		(
			[
				'id' => 'wc1c_help_tab',
				'title' => __('Help', 'wc1c-main'),
				'content' => wc1c()->views()->getViewHtml('/helps/main.php')
			]
		);

		$screen->add_help_tab
		(
			[
				'id' => 'wc1c_bugs_tab',
				'title' => __('Found a bug?', 'wc1c-main'),
				'content' => wc1c()->views()->getViewHtml('/helps/bugs.php')
			]
		);

		$screen->add_help_tab
		(
			[
				'id' => 'wc1c_features_tab',
				'title' => __('Not a feature?', 'wc1c-main'),
				'content' => wc1c()->views()->getViewHtml('/helps/features.php')
			]
		);
	}
}
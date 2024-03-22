<?php namespace Wc1c\Main;

defined('ABSPATH') || exit;

/**
 * Activation
 *
 * @package Wc1c\Main
 */
final class Activation extends \Digiom\Woplucore\Activation
{
	public function __construct()
	{
		if(false === get_option('wc1c_version', false))
		{
			update_option('wc1c_wizard', 'setup');

			wc1c()->admin()->notices()->create
			(
				[
					'id' => 'activation_welcome',
					'dismissible' => false,
					'type' => 'info',
					'data' => __('WC1C successfully activated. You have made the right choice to integrate the site with 1C (plugin number one)!', 'wc1c-main'),
					'extra_data' => sprintf
					(
						'<p>%s <a href="%s">%s</a></p>',
						__('The basic plugin setup has not been done yet, so can proceed to the setup, which takes no more than 5 minutes.', 'wc1c-main'),
						admin_url('admin.php?page=wc1c'),
						__('Go to setting.', 'wc1c-main')
					)
				]
			);
		}

		if(false === get_option('wc1c_version_init', false))
		{
			update_option('wc1c_version_init', wc1c()->environment()->get('wc1c_version'));
		}
	}
}
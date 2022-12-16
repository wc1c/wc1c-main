<?php namespace Wc1c\Main;

defined('ABSPATH') || exit;

/**
 * Context
 *
 * @package Wc1c\Main
 */
final class Context
{
	/**
	 * @var bool|null Internal storage for whether the plugin is network active or not.
	 */
	private $network_active = null;

	/**
	 * Context constructor.
	 */
	public function __construct()
	{
		do_action('wc1c_context_loaded');
	}

	/**
	 * Is Receiver request?
	 *
	 * @return bool
	 */
	public function isReceiver()
	{
		if(false === isset($_GET['wc1c-receiver']))
		{
			return false;
		}

		if(wc1c()->getVar($_GET['wc1c-receiver'], false))
		{
			return true;
		}

		return false;
	}

	/**
	 * Is admin request?
	 *
	 * @return bool
	 */
	public function isAdmin($type = 'plugin')
	{
		switch($type)
		{
			case 'wc1c':
			case 'plugin':
				if(false !== is_admin() && 'wc1c' === wc1c()->getVar($_GET['page'], ''))
				{
					return true;
				}
				break;
			case 'wp':
			case 'wordpress':
				if(false !== is_admin())
				{
					return true;
				}
				break;
			default:
				return false;
		}

		return false;
	}

	/**
	 * Determines whether the plugin is running in network mode.
	 *
	 * Network mode is active under the following conditions:
	 * * Multisite is enabled.
	 * * The plugin is network-active.
	 * * The site's domain matches the network's domain (which means it is a subdirectory site).
	 *
	 * @return bool True if the plugin is in network mode, false otherwise.
	 */
	public function isNetworkMode()
	{
		// Bail if plugin is not network-active.
		if(!$this->isNetworkActive())
		{
			return false;
		}

		$site = get_site(get_current_blog_id());

		if(is_null($site))
		{
			return false;
		}

		$network = get_network($site->network_id);

		// Use network mode when the site's domain is the same as the network's domain.
		return $network && $site->domain === $network->domain;
	}

	/**
	 * Checks whether the plugin is network active.
	 *
	 * @return bool True if plugin is network active, false otherwise.
	 */
	public function isNetworkActive()
	{
		// Determine $network_active property just once per request, to not unnecessarily run this complex logic on every call.
		if(null === $this->network_active)
		{
			if(is_multisite())
			{
				$network_active_plugins = wp_get_active_network_plugins();

				// Consider MU plugins and network-activated plugins as network-active.
				$this->network_active = strpos(wp_normalize_path(__FILE__), wp_normalize_path(WPMU_PLUGIN_DIR) ) === 0 || in_array(WP_PLUGIN_DIR . '/' . wc1c()->environment()->get('plugin_basename'), $network_active_plugins, true);
			}
			else
			{
				$this->network_active = false;
			}
		}

		return $this->network_active;
	}
}
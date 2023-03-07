<?php namespace Digiom\Woplucore;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Interfaces\Contextable;
use Digiom\Woplucore\Interfaces\Loadable;

/**
 * Context
 *
 * @package Digiom\Woplucore
 */
class Context implements Contextable
{
	/**
	 * @var string Unique slug
	 */
	protected $slug = 'plugin';

	/**
	 * @var string Absolute path to the plugin main file.
	 */
	protected $file;

	/**
	 * @var bool Internal storage for whether the plugin is network active or not.
	 */
	protected $network_active = false;

	/**
	 * @var Loadable Loader
	 */
	protected $loader;

	/**
	 * Context constructor.
	 */
	public function __construct($main_file, $slug, $loader = null)
	{
		$this->file = $main_file;
		$this->slug = $slug;

		if($loader instanceof Loadable)
		{
			$this->loader = $loader;
		}
	}

	/**
	 * @return string
	 */
	public function getSlug(): string
	{
		return $this->slug;
	}

	/**
	 * @return string
	 */
	public function getFile(): string
	{
		return $this->file;
	}

	/**
	 * @return Loadable
	 */
	public function loader(): Loadable
	{
		return $this->loader;
	}

	/**
	 * Setup loader for context
	 *
	 * @param Loadable $loader
	 *
	 * @return Contextable
	 */
	public function setLoader(Loadable $loader): Contextable
	{
		$this->loader = $loader;

		return $this;
	}

	/**
	 * Is admin?
	 *
	 * @return bool
	 */
	public function isAdmin(): bool
	{
		return is_admin();
	}

	/**
	 * Determines whether the plugin is running in network mode.
	 *
	 * Network mode is active under the following conditions:
	 * - Multisite is enabled.
	 * - The plugin is network-active.
	 * - The site's domain matches the network's domain (which means it is a subdirectory site).
	 *
	 * @return bool True if the plugin is in network mode, false otherwise.
	 */
	public function isNetworkMode(): bool
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
	public function isNetworkActive(): bool
	{
		// Determine $network_active property just once per request, to not unnecessarily run this complex logic on every call.
		if(null === $this->network_active)
		{
			$this->network_active = false;

			if(is_multisite())
			{
				$network_active_plugins = wp_get_active_network_plugins();

				// Consider MU plugins and network-activated plugins as network-active.
				$this->network_active = strpos(wp_normalize_path(__FILE__), wp_normalize_path(WPMU_PLUGIN_DIR) ) === 0 || in_array(WP_PLUGIN_DIR . '/' . plugin_basename($this->file), $network_active_plugins, true);
			}
		}

		return $this->network_active;
	}

	/**
	 * Calls the WordPress core functions to get the locale and return it in the required format.
	 *
	 * @param string $context Optional. Defines which WordPress core locale function to call.
	 * @param string $format Optional. Defines the format the locale is returned in.
	 *
	 * @return string Locale in the required format.
	 */
	public function getLocale(string $context = 'site', string $format = 'default'): string
	{
		$wp_locale = get_locale();

		if('user' === $context)
		{
			$wp_locale = get_user_locale();
		}

		if('language-code' === $format)
		{
			$code_array = explode('_', $wp_locale);

			return $code_array[0];
		}

		if('language-variant' === $format)
		{
			return implode('_', array_slice(explode('_', $wp_locale), 0, 2));
		}

		return $wp_locale;
	}
}
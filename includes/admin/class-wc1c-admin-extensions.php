<?php
/**
 * Extensions class
 *
 * @package Wc1c/Admin
 */
defined('ABSPATH') || exit;

class Wc1c_Admin_Extensions
{
	/**
	 * Wc1c_Admin_Extensions constructor
	 *
	 * @param bool $init
	 */
	public function __construct($init = true)
	{
		/**
		 * Auto init
		 */
		if($init)
		{
			$this->init();
		}
	}

	/**
	 * Initialized
	 */
	public function init()
	{
		/**
		 * Output table
		 */
		add_action('wc1c_admin_extensions_show', array($this, 'output'), 10);
	}

	/**
	 * Output tools table
	 *
	 * @return void
	 */
	public function output()
	{
		$extensions = WC1C()->get_extensions();

		if(empty($extensions))
		{
			wc1c_get_template('extensions_404.php');
			return;
		}

		wc1c_get_template('extensions_header.php');

		foreach($extensions as $extension_id => $extension_object)
		{
			$args =
			[
				'id' => $extension_id,
				'object' => $extension_object
			];

			wc1c_get_template('extensions_item.php', $args);
		}
	}
}
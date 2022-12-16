<?php namespace Wc1c\Main\Admin;

defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Extensions\All;
use Wc1c\Main\Traits\SingletonTrait;

/**
 * Extensions
 *
 * @package Wc1c\Main\Admin
 */
final class Extensions
{
	use SingletonTrait;

	/**
	 * @var array Available actions
	 */
	private $actions =
	[
		'all',
	];

	/**
	 * @var string Current action
	 */
	private $current_action = 'all';

	/**
	 * Extensions constructor.
	 */
	public function __construct()
	{
		$actions = apply_filters('wc1c_admin_extensions_init_actions', $this->actions);

		$this->setActions($actions);

		$current_action = $this->initCurrentAction();

		switch($current_action)
		{
			case 'all':
				All::instance();
				break;
			default:
				All::instance();
		}
	}

	/**
	 * Current action
	 *
	 * @return string
	 */
	public function initCurrentAction()
	{
		$do_action = wc1c()->getVar($_GET['do_action'], 'all');

		if(in_array($do_action, $this->getActions(), true))
		{
			$this->setCurrentAction($do_action);
		}

		return $this->getCurrentAction();
	}

	/**
	 * Get actions
	 *
	 * @return array
	 */
	public function getActions()
	{
		return apply_filters('wc1c_admin_extensions_get_actions', $this->actions);
	}

	/**
	 * Set actions
	 *
	 * @param array $actions
	 */
	public function setActions($actions)
	{
		// hook
		$actions = apply_filters('wc1c_admin_extensions_set_actions', $actions);

		$this->actions = $actions;
	}

	/**
	 * Get current action
	 *
	 * @return string
	 */
	public function getCurrentAction()
	{
		return apply_filters('wc1c_admin_extensions_get_current_action', $this->current_action);
	}

	/**
	 * Set current action
	 *
	 * @param string $current_action
	 */
	public function setCurrentAction($current_action)
	{
		// hook
		$current_action = apply_filters('wc1c_admin_extensions_set_current_action', $current_action);

		$this->current_action = $current_action;
	}
}
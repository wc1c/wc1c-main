<?php namespace Wc1c\Main;

defined('ABSPATH') || exit;

/**
 * Context
 *
 * @package Wc1c\Main
 */
final class Context extends \Digiom\Woplucore\Context
{
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
}
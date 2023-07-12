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
	public function isReceiver(): bool
    {
		if(isset($_GET['wc1c-receiver']))
		{
			return true;
		}

        if(isset($_POST['wc1c-receiver']))
        {
            return true;
        }

        if(isset($_REQUEST['wc1c-receiver']))
        {
            return true;
        }

		return false;
	}
}
<?php namespace Wc1c\Main;

defined('ABSPATH') || exit;

use Digiom\Woap\Client;

/**
 * Connection
 *
 * @package Wc1c\Main
 */
final class Connection extends Client
{
	/**
	 * @var string
	 */
	protected $host = 'https://wc1c.info';
}
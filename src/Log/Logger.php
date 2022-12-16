<?php namespace Wc1c\Main\Log;

defined('ABSPATH') || exit;

use Monolog\Logger as Monolog;

/**
 * Logger
 *
 * @package Wc1c\Main
 */
final class Logger extends Monolog
{
	/**
	 * @var string
	 */
	protected $name = 'main';
}
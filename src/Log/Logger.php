<?php namespace Wc1c\Main\Log;

defined('ABSPATH') || exit;

/**
 * Logger
 *
 * @package Wc1c\Main
 */
final class Logger extends \Monolog\Logger
{
	/**
	 * @var string
	 */
	protected $name = 'main';
}
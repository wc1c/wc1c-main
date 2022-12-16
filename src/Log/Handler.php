<?php namespace Wc1c\Main\Log;

defined('ABSPATH') || exit;

use Monolog\Handler\RotatingFileHandler;

/**
 * Handler
 *
 * @package Wc1c\Main
 */
final class Handler extends RotatingFileHandler
{
}
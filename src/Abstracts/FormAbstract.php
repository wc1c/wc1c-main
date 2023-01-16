<?php namespace Wc1c\Main\Abstracts;

defined('ABSPATH') || exit;

/**
 * FormAbstract
 *
 * @package Wc1c\Main\Abstracts
 */
abstract class FormAbstract extends \Digiom\Woplucore\Abstracts\FormAbstract
{
	/**
	 * @var string Unique prefix
	 */
	protected $prefix = 'wc1c';
}
<?php namespace Wc1c\Main\Tecodes;

defined('ABSPATH') || exit;

use Tecodes_Local_Storage_Code;

/**
 * Tecodes storage code class
 *
 * @package Wc1c\Main\Tecodes
 */
class Storage extends Tecodes_Local_Storage_Code
{
	/**
	 * @var string
	 */
	protected $option_name = 'wc1c_tecodes_code';
}
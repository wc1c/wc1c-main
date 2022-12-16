<?php namespace Wc1c\Main\Admin\Configurations;

defined('ABSPATH') || exit;

use Wc1c\Main\Abstracts\ScreenAbstract;
use Wc1c\Main\Traits\SingletonTrait;

/**
 * All
 *
 * @package Wc1c\Main\Admin\Configurations
 */
class All extends ScreenAbstract
{
	use SingletonTrait;

	/**
	 * Build and output table
	 */
	public function output()
	{
		$list_table = new AllTable();

		$args['object'] = $list_table;

		wc1c()->views()->getView('configurations/all.php', $args);
	}
}
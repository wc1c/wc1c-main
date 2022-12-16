<?php namespace Wc1c\Main\Admin\Wizards\Setup;

defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Wizards\StepAbstract;
use Wc1c\Main\Traits\SingletonTrait;

/**
 * Check
 *
 * @package Wc1c\Main\Admin\Wizards
 */
class Check extends StepAbstract
{
	use SingletonTrait;

	/**
	 * Check constructor.
	 */
	public function __construct()
	{
		$this->setId('check');
	}

	/**
	 * Precessing step
	 */
	public function process()
	{
		add_action('wc1c_wizard_content_output', [$this, 'output'], 10);
	}

	/**
	 * Output wizard content
	 *
	 * @return void
	 */
	public function output()
	{
		$args =
		[
			'step' => $this
		];

		wc1c()->views()->getView('wizards/steps/check.php', $args);
	}
}
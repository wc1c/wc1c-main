<?php namespace Wc1c\Main\Admin\Wizards\Setup;

defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Wizards\StepAbstract;
use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Traits\UtilityTrait;

/**
 * Complete
 *
 * @package Wc1c\Main\Admin\Wizards
 */
class Complete extends StepAbstract
{
	use SingletonTrait;
	use UtilityTrait;

	/**
	 * Complete constructor.
	 */
	public function __construct()
	{
		$this->setId('complete');
	}

	/**
	 * Precessing step
	 */
	public function process()
	{
		delete_option('wc1c_wizard');
		update_option('wc1c_version', wc1c()->environment()->get('wc1c_version'));

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
			'step' => $this,
			'back_url' => $this->utilityAdminConfigurationsGetUrl('all'),
		];

		wc1c()->views()->getView('wizards/steps/complete.php', $args);
	}
}
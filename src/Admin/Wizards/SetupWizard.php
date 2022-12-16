<?php namespace Wc1c\Main\Admin\Wizards;

defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Wizards\Setup\Check;
use Wc1c\Main\Admin\Wizards\Setup\Complete;
use Wc1c\Main\Admin\Wizards\Setup\Database;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Traits\SingletonTrait;

/**
 * SetupWizard
 *
 * @package Wc1c\Main\Admin\Wizards
 */
final class SetupWizard extends WizardAbstract
{
	use SingletonTrait;

	/**
	 * SetupWizard constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->setId('setup');
		$this->setDefaultSteps();
		$this->setStep(isset($_GET[$this->getId()]) ? sanitize_key($_GET[$this->getId()]) : current(array_keys($this->getSteps())));

		$this->init();
	}

	/**
	 * Initialize
	 */
	public function init()
	{
		add_filter('wc1c_admin_init_sections', [$this, 'hideSections'], 20, 1);
		add_filter('wc1c_admin_init_sections_current', [$this, 'setSectionsCurrent'], 20, 1);
		add_action('wc1c_admin_show', [$this, 'route']);
	}

	/**
	 * @param $sections
	 *
	 * @return array
	 */
	public function hideSections($sections)
	{
		$default_sections[$this->getId()] =
		[
			'title' => __('Setup wizard', 'wc1c-main'),
			'visible' => true,
			'callback' => [__CLASS__, 'instance']
		];

		return $default_sections;
	}

	/**
	 * @param $section
	 *
	 * @return string
	 */
	public function setSectionsCurrent($section)
	{
		return $this->getId();
	}

	/**
	 * @return void
	 */
	private function setDefaultSteps()
	{
		$default_steps =
		[
			'check' =>
			[
				'name' => __('Compatibility', 'wc1c-main'),
				'callback' => [Check::class, 'instance'],
			],
			'database' =>
			[
				'name' => __('Database', 'wc1c-main'),
				'callback' => [Database::class, 'instance'],
			],
			'complete' =>
			[
				'name' => __('Completing', 'wc1c-main'),
				'callback' => [Complete::class, 'instance'],
			],
		];

		$this->setSteps($default_steps);
	}
}
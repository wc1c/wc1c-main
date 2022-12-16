<?php namespace Wc1c\Main\Admin\Wizards;

defined('ABSPATH') || exit;

/**
 * StepAbstract
 *
 * @package Wc1c\Main\Admin\Wizards
 */
abstract class StepAbstract
{
	/**
	 * @var WizardAbstract
	 */
	private $wizard;

	/**
	 * @var string Unique step id
	 */
	private $id = '';

	/**
	 * @param WizardAbstract $wizard
	 */
	public function setWizard($wizard)
	{
		$this->wizard = $wizard;
	}

	/**
	 * @return WizardAbstract
	 */
	public function wizard()
	{
		return $this->wizard;
	}

	/**
	 * @param $id
	 *
	 * @return void
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	abstract public function process();
}
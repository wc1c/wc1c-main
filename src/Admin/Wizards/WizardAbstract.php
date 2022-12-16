<?php namespace Wc1c\Main\Admin\Wizards;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;

/**
 * WizardAbstract
 *
 * @package Wc1c\Main\Admin\Wizards
 */
abstract class WizardAbstract
{
	/**
	 * @var string
	 */
	private $id = 'wizards';

	/**
	 * @var string Current Step
	 */
	private $step = '';

	/**
	 * @var array All steps
	 */
	private $steps;

	/**
	 * @param $id
	 *
	 * @return void
	 * @throws Exception
	 */
	public function setId($id)
	{
		if(empty($id))
		{
			throw new Exception('wizards id is empty');
		}

		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getStep()
	{
		return $this->step;
	}

	/**
	 * @param string $step
	 */
	public function setStep($step)
	{
		$this->step = $step;
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
	abstract public function init();

	/**
	 * @return bool
	 */
	public function isEmptySteps()
	{
		return empty($this->steps);
	}

	/**
	 * @return string
	 */
	public function getNextStepLink()
	{
		return $this->getLinkByStep($this->getNextStep());
	}

	/**
	 * @return string
	 */
	public function getPrevStepLink()
	{
		return $this->getLinkByStep($this->getPrevStep());
	}

	/**
	 * @return string
	 */
	public function getLinkByStep($step)
	{
		return add_query_arg($this->getId(), $step);
	}

	/**
	 * @return string
	 */
	public function getNextStep()
	{
		$step = $this->getStep();
		$keys = array_keys($this->getSteps());

		if(end($keys) === $step)
		{
			return '';
		}

		$step_index = array_search($step, $keys, true);
		if(false === $step_index)
		{
			return '';
		}

		return $keys[$step_index + 1];
	}

	/**
	 * @return string
	 */
	public function getPrevStep()
	{
		$steps = $this->getSteps();
		$keys = array_keys($steps);

		$current_step = $keys[array_search($this->step, array_keys($steps), true)];

		return $current_step - 1;
	}

	/**
	 * @return array
	 */
	public function getSteps()
	{
		return $this->steps;
	}

	/**
	 * @param array $steps
	 */
	public function setSteps($steps)
	{
		$this->steps = apply_filters('wc1c_wizards_steps', $steps, $this->getId());
	}

	/**
	 * Route steps
	 */
	public function route()
	{
		$steps = $this->getSteps();
		$current = $this->getStep();

		if(!array_key_exists($current, $steps) || !isset($steps[$current]['callback']))
		{
			add_action('wc1c_wizard_content_output', [$this, 'error']);
		}
		else
		{
			$callback = $steps[$current]['callback'];

			if(is_callable($callback, false, $callback_name))
			{
				$step = $callback_name();
				$step->setWizard($this);
				$step->process();
			}
		}

		$args =
		[
			'wizard' => $this
		];

		wc1c()->views()->getView('wizards/page.php', $args);
	}

	/**
	 * Error
	 */
	public function error()
	{
		wc1c()->views()->getView('wizards/error.php');
	}
}
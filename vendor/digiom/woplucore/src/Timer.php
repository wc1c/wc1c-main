<?php namespace Digiom\Woplucore;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Traits\SingletonTrait;

/**
 * Timer
 *
 * @package Digiom\Woplucore
 */
class Timer
{
	use SingletonTrait;

	/**
	 * @var int Maximum time is available in sec
	 */
	private $maximum = 30;

	/**
	 * @var int|float Timer started
	 */
	private $started = 0;

	/**
	 * Timer constructor.
	 */
	public function __construct()
	{
		if(isset($_SERVER['REQUEST_TIME_FLOAT']))
		{
			$this->setStarted($_SERVER['REQUEST_TIME_FLOAT']);
		}
	}

	/**
	 * Available maximum secs
	 *
	 * @return int
	 */
	public function getMaximum(): int
	{
		return $this->maximum;
	}

	/**
	 * Set maximum available secs
	 *
	 * @param int $maximum
	 */
	public function setMaximum(int $maximum)
	{
		$this->maximum = $maximum;
	}

	/**
	 * Timer started
	 *
	 * @return int
	 */
	public function getStarted()
	{
		return $this->started;
	}

	/**
	 * Set timer started
	 *
	 * @param int|float $started
	 */
	public function setStarted($started)
	{
		$this->started = $started;
	}

	/**
	 * Current executions time in secs
	 *
	 * @return float|int
	 */
	public function getExecutionSeconds()
	{
		return microtime(true) - $this->getStarted();
	}

	/**
	 * Available secs for executions
	 *
	 * @return float|int
	 */
	public function getAvailableSeconds()
	{
		return $this->getMaximum() - $this->getExecutionSeconds();
	}

	/**
	 * Get available seconds bigger than secs
	 *
	 * @param int|float $seconds
	 *
	 * @return bool
	 */
	public function isRemainingBiggerThan(int $seconds = 30): bool
	{
		$max_execution_time = $this->getMaximum();

		if($max_execution_time === 0)
		{
			return true;
		}

		$remaining_seconds = $this->getAvailableSeconds();

		return ($remaining_seconds >= $seconds);
	}

	/**
	 * Wrapper for set_time_limit to see if it is enabled
	 *
	 * @param int $limit time limit
	 *
	 * @return bool
	 */
	public function setTimeLimit(int $limit = 0): bool
	{
		if(function_exists('set_time_limit') && false === strpos(ini_get('disable_functions'), 'set_time_limit'))
		{
			set_time_limit($limit);
			return true;
		}

		return false;
	}
}
<?php namespace Wc1c\Main\Admin\Traits;

defined('ABSPATH') || exit;

use Wc1c\Main\Configuration;
use Wc1c\Main\Exceptions\Exception;

/**
 * ProcessConfigurationTrait
 *
 * @package Wc1c\Main\Admin\Traits
 */
trait ProcessConfigurationTrait
{
	/**
	 * @var Configuration
	 */
	protected $configuration;

	/**
	 * @param $configuration_id
	 *
	 * @return bool
	 */
	public function setConfiguration($configuration_id)
	{
		if($configuration_id instanceof Configuration)
		{
			$this->configuration = $configuration_id;

			return false;
		}

		$error = false;

		try
		{
			$configuration = new Configuration($configuration_id);

			if(!$configuration->getStorage()->isExistingById($configuration_id))
			{
				$error = true;
			}

			$this->configuration = $configuration;
		}
		catch(Exception $e)
		{
			$error = true;
		}

		return $error;
	}

	/**
	 * @return Configuration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}
}
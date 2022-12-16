<?php namespace Wc1c\Wc\Products\Traits;

defined('ABSPATH') || exit;

/**
 * ConfigurationProductTrait
 *
 * @package Wc1c\Wc
 */
trait ConfigurationProductTrait
{
	/**
	 * @param $id
	 *
	 * @return void
	 */
	public function setConfigurationId($id)
	{
		$this->add_meta_data('_wc1c_configuration_id', $id, true);
	}

	/**
	 * @param string $context
	 *
	 * @return array|string
	 */
	public function getConfigurationId($context = 'view')
	{
		return $this->get_meta('_wc1c_configuration_id', true, $context);
	}
}
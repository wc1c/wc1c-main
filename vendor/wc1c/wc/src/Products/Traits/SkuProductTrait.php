<?php namespace Wc1c\Wc\Products\Traits;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;

/**
 * SkuProductTrait
 *
 * @package Wc1c\Wc
 */
trait SkuProductTrait
{
	/**
	 * Установка артикула продукта с опциональным учетом уникальности
	 *
	 * @param string $sku Артикул продукта
	 * @param boolean $unique Требовать ли уникальное значение
	 *
	 * @return void
	 * @throws Exception
	 */
	public function setSku($sku, $unique = false)
	{
		$sku = (string)$sku;

		if(false === $unique)
		{
			$this->set_prop('sku', $sku);
		}

		try
		{
			$this->set_sku($sku);
		}
		catch(\Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Получение текущего артикула продукта
	 *
	 * @param string $context Контекст запроса
	 *
	 * @return string
	 */
	public function getSku($context = 'view')
	{
		return $this->get_sku();
	}
}
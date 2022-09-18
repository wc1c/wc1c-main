<?php namespace Wc1c\Cml\Contracts;

defined('ABSPATH') || exit;

/**
 * ProductDataContract
 *
 * @package Wc1c\Cml
 */
interface ProductDataContract extends DataContract
{
	/**
	 * Получение уникального идентификатора продукта в рамках текущего каталога товаров CommerceML
	 *
	 * @return string Product id
	 */
	public function getId();

	/**
	 * Получение уникального идентификатора характеристики продукта в рамках текущего каталога товаров CommerceML
	 *
	 * @return string Characteristic
	 */
	public function getCharacteristicId();

	/**
	 * Имеется ли у продукта характеристика
	 *
	 * @return boolean
	 */
	public function hasCharacteristicId();

	/**
	 * Получение наименования продукта
	 *
	 * @return string Product name
	 */
	public function getName();

	/**
	 * Получение описания продукта
	 *
	 * @return string Product description
	 */
	public function getDescription();

	/**
	 * Имеются ли у продукта группы описанные в классификаторе
	 *
	 * @return boolean
	 */
	public function hasClassifierGroups();

	/**
	 * Получение массива идентификаторов групп для продукта описанных в классификаторе
	 *
	 * @return array Product groups
	 */
	public function getClassifierGroups();

	/**
	 * Проверка на наличие реквизитов у продукта, возможна проверка конкретного реквизита
	 *
	 * @param string $name Наименование реквизита
	 *
	 * @return bool Имеются ли реквизиты
	 */
	public function hasRequisites($name = '');

	/**
	 * Получение реквизитов продукта
	 *
	 * @param string $name Наименование реквизита для получения значения, опционально
	 *
	 * @return false|array|string Ложь, массив всех реквизитов или значение конкретного реквизита
	 */
	public function getRequisites($name = '');

	/**
	 * Имеются ли у продукта цены или конкретная цена по идентификатору типа цены
	 *
	 * @param string $price_type_id Идентификатор типа цены
	 *
	 * @return bool
	 */
	public function hasPrices($price_type_id = '');
}
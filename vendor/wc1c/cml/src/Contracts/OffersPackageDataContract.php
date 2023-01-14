<?php namespace Wc1c\Cml\Contracts;

defined('ABSPATH') || exit;

/**
 * OffersPackageDataContract
 *
 * @package Wc1c\Cml
 */
interface OffersPackageDataContract extends DataContract
{
	/**
	 * Получение уникального идентификатора пакета предложений
	 *
	 * @return string Unique identifier
	 */
	public function getId(): string;

	/**
	 * Получение наименования пакета предложений
	 *
	 * @return string Name
	 */
	public function getName(): string;

	/**
	 * Получение идентификатора каталога товаров
	 *
	 * @return string Catalog identifier
	 */
	public function getCatalogId(): string;

	/**
	 * Получение идентификатора классификатора, по которому описан каталог товаров
	 *
	 * @return string Classifier identifier
	 */
	public function getClassifierId(): string;

	/**
	 * Получение владельца каталога предложений
	 *
	 * @return CounterpartyDataContract Catalog owner
	 */
	public function getOwner(): CounterpartyDataContract;

	/**
	 * Пакет содержит только изменения, или нет.
	 *
	 * @return bool
	 */
	public function isOnlyChanges(): bool;

	/**
	 * Установка маркера наличия только изменений в каталоге предложений
	 *
	 * @param bool $only_changes
	 */
	public function setOnlyChanges(bool $only_changes);
}
<?php namespace Wc1c\Cml\Contracts;

/**
 * CatalogDataContract
 *
 * @package Wc1c\Cml
 */
interface CatalogDataContract extends DataContract
{
	/**
	 * @return string Unique identifier
	 */
	public function getId(): string;

	/**
	 * @return string Classifier identifier
	 */
	public function getClassifierId(): string;

	/**
	 * @return string Catalog name
	 */
	public function getName(): string;

	/**
	 * @return CounterpartyDataContract Catalog owner
	 */
	public function getOwner(): CounterpartyDataContract;

    /**
     * Каталог содержит только изменения, или нет.
     *
     * @return bool
     */
    public function isOnlyChanges(): bool;

    /**
     * Установка маркера наличия только изменений в каталоге товаров
     *
     * @param bool $only_changes
     */
    public function setOnlyChanges(bool $only_changes);
}
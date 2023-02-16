<?php namespace Wc1c\Cml\Entities;

defined('ABSPATH') || exit;

use Wc1c\Cml\Abstracts\DataAbstract;
use Wc1c\Cml\Contracts\CounterpartyDataContract;
use Wc1c\Cml\Contracts\OffersPackageDataContract;

/**
 * OffersPackage
 *
 * @package Wc1c\Cml
 */
class OffersPackage extends DataAbstract implements OffersPackageDataContract
{
	/**
	 * @var array
	 */
	protected $data =
	[
		'id' => '',
		'classifier_id' => '',
		'catalog_id' => '',
		'name' => '',
		'owner' => null,
		'price_types' => [],
		'warehouses' => [],
		'only_changes' => false,
	];

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->data['id'];
	}

	/**
	 * @param string $id
	 */
	public function setId(string $id)
	{
		$this->data['id'] = $id;
	}

	/**
	 * @return string
	 */
	public function getClassifierId(): string
	{
		return $this->data['classifier_id'];
	}

	/**
	 * @param string $id
	 */
	public function setClassifierId(string $id)
	{
		$this->data['classifier_id'] = $id;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->data['name'];
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name)
	{
		$this->data['name'] = $name;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->data['description'];
	}

	/**
	 * @param string $description
	 */
	public function setDescription(string $description)
	{
		$this->data['description'] = $description;
	}

	/**
	 * @return CounterpartyDataContract
	 */
	public function getOwner(): CounterpartyDataContract
	{
		return $this->data['owner'];
	}

	/**
	 * @param CounterpartyDataContract $owner
	 */
	public function setOwner(CounterpartyDataContract $owner)
	{
		$this->data['owner'] = $owner;
	}

	/**
	 * @return bool
	 */
	public function isOnlyChanges(): bool
	{
		return $this->data['only_changes'];
	}

	/**
	 * @param bool $only_changes
	 */
	public function setOnlyChanges(bool $only_changes)
	{
		$this->data['only_changes'] = $only_changes;
	}

	/**
	 * @return string
	 */
	public function getCatalogId(): string
	{
		return $this->data['catalog_id'];
	}

	/**
	 * @param string $id
	 */
	public function setCatalogId(string $id)
	{
		$this->data['catalog_id'] = $id;
	}

	/**
	 * @param array $data
	 *
	 * @return void
	 */
	public function setPriceTypes(array $data)
	{
		$this->data['price_types'] = $data;
	}

	/**
	 * @return array
	 */
	public function getPriceTypes(): array
	{
		return $this->data['price_types'];
	}

	/**
	 * @param array $warehouses
	 */
	public function setWarehouses(array $warehouses)
	{
		$this->data['warehouses'] = $warehouses;
	}

	/**
	 * @return array
	 */
	public function getWarehouses(): array
	{
		return $this->data['warehouses'];
	}

	/**
	 * @param array $price_types
	 *
	 * @return void
	 */
	public function assignPriceTypes(array $price_types)
	{
		$this->data['price_types'] = array_merge($this->data['price_types'], $price_types);
	}

	/**
	 * @param array $warehouses
	 */
	public function assignWarehouses(array $warehouses)
	{
		$this->data['warehouses'] = array_merge($this->data['warehouses'], $warehouses);
	}

	/**
	 * @return bool
	 */
	public function hasPriceTypes(): bool
	{
		if(empty($this->data['price_types']))
		{
			return false;
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasWarehouses(): bool
	{
		if(empty($this->data['warehouses']))
		{
			return false;
		}
		return true;
	}
}
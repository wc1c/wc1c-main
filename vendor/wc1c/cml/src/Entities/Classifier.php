<?php namespace Wc1c\Cml\Entities;

defined('ABSPATH') || exit;

use Wc1c\Cml\Abstracts\DataAbstract;
use Wc1c\Cml\Contracts\ClassifierDataContract;
use Wc1c\Cml\Contracts\CounterpartyDataContract;

/**
 * Classifier
 *
 * @package Wc1c\Cml
 */
class Classifier extends DataAbstract implements ClassifierDataContract
{
	/**
	 * @var array
	 */
	protected $data =
	[
		'id' => '',
		'name' => '',
		'description' => '',
		'owner' => null,
		'groups' => [],
		'categories' => [],
		'units' => [],
		'properties' => [],
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
	 * @return array
	 */
	public function getGroups(): array
	{
		return $this->data['groups'];
	}

	/**
	 * @param array $groups
	 */
	public function setGroups(array $groups)
	{
		$this->data['groups'] = $groups;
	}

	/**
	 * @param array $groups
	 */
	public function assignGroups(array $groups)
	{
		$this->data['groups'] = array_merge($this->data['groups'], $groups);
	}

	/**
	 * @return array
	 */
	public function getCategories(): array
	{
		return $this->data['categories'];
	}

	/**
	 * @param array $categories
	 */
	public function setCategories(array $categories)
	{
		$this->data['categories'] = $categories;
	}

	/**
	 * @param array $categories
	 */
	public function assignCategories(array $categories)
	{
		$this->data['categories'] = array_merge($this->data['categories'], $categories);
	}

	/**
	 * @return array
	 */
	public function getUnits(): array
	{
		return $this->data['units'];
	}

	/**
	 * @param array $units
	 */
	public function setUnits(array $units)
	{
		$this->data['units'] = $units;
	}

	/**
	 * @param array $units
	 */
	public function assignUnits(array $units)
	{
		$this->data['units'] = array_merge($this->data['units'], $units);
	}

	/**
	 * @return array
	 */
	public function getProperties(): array
	{
		return $this->data['properties'];
	}

	/**
	 * @param array $properties
	 *
	 * @return void
	 */
	public function setProperties(array $properties)
	{
		$this->data['properties'] = $properties;
	}

	/**
	 * @param array $properties
	 *
	 * @return void
	 */
	public function assignProperties(array $properties)
	{
		$this->data['properties'] = array_merge($this->data['properties'], $properties);
	}

	/**
	 * @return array
	 */
	public function getPriceTypes(): array
	{
		return $this->data['price_types'];
	}

	/**
	 * @param array $price_types
	 *
	 * @return void
	 */
	public function setPriceTypes(array $price_types)
	{
		$this->data['price_types'] = $price_types;
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
	 * @return bool
	 */
	public function hasGroups(): bool
	{
		if(empty($this->data['groups']))
		{
			return false;
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasCategories(): bool
	{
		if(empty($this->data['categories']))
		{
			return false;
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasUnits(): bool
	{
		if(empty($this->data['units']))
		{
			return false;
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function hasProperties(): bool
	{
		if(empty($this->data['properties']))
		{
			return false;
		}
		return true;
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
     * @param array $warehouses
     */
    public function setWarehouses(array $warehouses)
    {
        $this->data['warehouses'] = $warehouses;
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
	public function hasWarehouses(): bool
	{
		if(empty($this->data['warehouses']))
		{
			return false;
		}
		return true;
	}

    /**
     * @return array
     */
    public function getWarehouses(): array
    {
        return $this->data['warehouses'];
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
}
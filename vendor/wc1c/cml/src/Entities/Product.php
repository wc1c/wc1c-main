<?php namespace Wc1c\Cml\Entities;

use Wc1c\Cml\Abstracts\DataAbstract;
use Wc1c\Cml\Contracts\ProductDataContract;

/**
 * Product
 *
 * @package Wc1c\Cml
 */
class Product extends DataAbstract implements ProductDataContract
{
	/**
	 * @var array
	 */
	protected $data =
	[
		'sku' => '',
		'name' => '',
		'description' => '',
		'quantity' => null,
		'images' => [],
		'prices' => [],
		'requisites' => [],
		'property_values' => [],
		'characteristic_values' => [],
		'taxes' => [],
		'classifier_groups' => [],
		'classifier_categories' => [],
		'base_unit' => [],
        'barcode' => '',
	];

	/**
	 * @return string|false
	 */
	public function getId()
	{
		if(empty($this->data['id']))
		{
			return false;
		}

		return $this->data['id'];
	}

	/**
	 * @return string|false
	 */
	public function getSku()
	{
		if(empty($this->data['sku']))
		{
			return false;
		}

		return $this->data['sku'];
	}

    /**
     * @return string|false
     */
    public function getBarcode()
    {
        if(empty($this->data['barcode']))
        {
            return false;
        }

        return $this->data['barcode'];
    }

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function setId($id)
	{
		$this->data['id'] = $id;

		return $this->data['id'];
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		if(empty($this->data['name']))
		{
			return '';
		}

		return $this->data['name'];
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	public function setName($name): string
	{
		$this->data['name'] = $name;

		return $this->data['name'];
	}

	/**
	 * @return false|string
	 */
	public function getDescription()
	{
		if(empty($this->data['description']))
		{
			return false;
		}

		return $this->data['description'];
	}

	/**
	 * Получение реквизитов продукта
	 *
	 * @param string $name Наименование реквизита для получения значения, опционально
	 *
	 * @return false|array Ложь, массив всех реквизитов или значение конкретного реквизита
	 */
	public function getRequisites($name = '')
	{
		if(!$this->hasRequisites())
		{
			return false;
		}

		if('' !== $name)
		{
			if($this->hasRequisites($name))
			{
				return $this->data['requisites'][$name];
			}

			return false;
		}

		return $this->data['requisites'];
	}

	/**
	 * Проверка на наличие реквизитов у продукта, возможна проверка конкретного реквизита
	 *
	 * @param string $name Наименование реквизита
	 *
	 * @return bool Имеются ли реквизиты
	 */
	public function hasRequisites($name = ''): bool
	{
		if(empty($this->data['requisites']))
		{
			return false;
		}

		if('' !== $name)
		{
			if(isset($this->data['requisites'][$name]))
			{
				return true;
			}

			return false;
		}

		return true;
	}

	/**
	 * @return false|array
	 */
	public function getCharacteristics()
	{
		if(!$this->hasCharacteristics())
		{
			return false;
		}

		return $this->data['characteristics'];
	}

	/**
	 * @return bool
	 */
	public function hasCharacteristics(): bool
	{
		if(empty($this->data['characteristics']))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return false|array
	 */
	public function getPropertyValues()
	{
		if(!$this->hasPropertyValues())
		{
			return false;
		}

		return $this->data['property_values'];
	}

	/**
	 * @return false|array
	 */
	public function getImages()
	{
		if(!$this->hasImages())
		{
			return false;
		}

		return $this->data['images'];
	}

	/**
	 * Имеются ли у продукта цены или конкретная цена по идентификатору типа цены
	 *
	 * @param string $price_type_id Идентификатор типа цены
	 *
	 * @return bool
	 */
	public function hasPrices($price_type_id = ''): bool
	{
		if(empty($this->data['prices']))
		{
			return false;
		}

		if('' === $price_type_id)
		{
			return true;
		}

		if(isset($this->data['prices'][$price_type_id]))
		{
			return true;
		}

		return false;
	}

	/**
	 * Получение текущих цен продукта
	 *
	 * @return false|array
	 */
	public function getPrices()
	{
		if(!$this->hasPrices())
		{
			return false;
		}

		return $this->data['prices'];
	}

	/**
	 * @return string
	 */
	public function getCharacteristicId(): string
	{
		if(!isset($this->data['characteristic_id']))
		{
			return '';
		}

		return $this->data['characteristic_id'];
	}

	/**
	 * @return bool
	 */
	public function hasCharacteristicId(): bool
	{
		return $this->getCharacteristicId() !== '';
	}

	/**
	 * @return bool
	 */
	public function hasClassifierGroups(): bool
	{
		if(empty($this->data['classifier_groups']))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function hasPropertyValues(): bool
	{
		if(empty($this->data['property_values']))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function hasBaseUnit(): bool
	{
		if(empty($this->data['base_unit']))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function hasImages(): bool
	{
		if(empty($this->data['images']))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function getClassifierGroups(): array
	{
		if(empty($this->data['classifier_groups']))
		{
			return [];
		}

		return $this->data['classifier_groups'];
	}

	/**
	 * @return array
	 */
	public function getClassifierCategories(): array
	{
		if(empty($this->data['classifier_categories']))
		{
			return [];
		}

		return $this->data['classifier_categories'];
	}

	/**
	 * @return array
	 */
	public function getTaxes(): array
	{
		if($this->hasTaxes())
		{
			return $this->data['taxes'];
		}

		return [];
	}

	/**
	 * @return array
	 */
	public function getBaseUnit(): array
	{
		if($this->hasBaseUnit())
		{
			return $this->data['base_unit'];
		}

		return [];
	}

	/**
	 * @return bool
	 */
	public function hasTaxes(): bool
	{
		if(empty($this->data['taxes']))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return int|float
	 */
	public function getQuantity()
	{
		if(empty($this->data['quantity']) || is_null($this->data['quantity']))
		{
			return 0;
		}

		return $this->data['quantity'];
	}

    /**
     * @return bool
     */
    public function hasDeleted(): bool
    {
        if(isset($this->data['delete_mark']) && $this->data['delete_mark'] === 'yes')
        {
            return true;
        }

        return false;
    }
}
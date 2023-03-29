<?php namespace Wc1c\Wc\Entities;

defined('ABSPATH') || exit;

use Wc1c\Wc\Abstracts\CategoriesData;
use Wc1c\Wc\Contracts\CategoryContract;

/**
 * Category
 *
 * @package Wc1c\Wc
 */
class Category extends CategoriesData implements CategoryContract
{
	/**
	 * Текущие данные Категории
	 *
	 * @var array
	 */
	protected $data =
	[
		'parent_id' => 0,
		'name' => '',
		'description' => '',
		'slug' => '',
		'image_id' => 0,
		'display_type' => '',
	];

	/**
	 * Установка наименования
	 *
	 * @param string $name Наименование категории
	 *
	 * @return void
	 */
	public function setName($name)
	{
		$this->setProp('name', $name);
	}

	/**
	 * Получение наименования категории
	 *
	 * @param string $context Контекст запроса
	 *
	 * @return string Наименование категории
	 */
	public function getName($context = 'view'): string
	{
		return $this->getProp('name', $context);
	}

	/**
	 * Получение слага категории
	 *
	 * @param string $context Контекст запроса
	 *
	 * @return string Слаг категории
	 */
	public function getSlug(string $context = 'view'): string
	{
		return $this->getProp('slug', $context);
	}

	/**
	 * Установка слага категории
	 *
	 * @param string $slug Слаг категории
	 *
	 * @return void
	 */
	public function setSlug(string $slug)
	{
		$this->setProp('slug', $slug);
	}

	/**
	 * Установка описания категории
	 *
	 * @param string $description Описание категории
	 *
	 * @return void
	 */
	public function setDescription($description)
	{
		$this->setProp('description', $description);
	}

	/**
	 * Получение описания категории
	 *
	 * @param string $context Контекст запроса
	 *
	 * @return string Описание категории
	 */
	public function getDescription($context = 'view'): string
	{
		return $this->getProp('description', $context);
	}

	/**
	 * Установка идентификатора схемы через которую была создана категория
	 *
	 * @param string|int $id Идентификатор схемы
	 *
	 * @return void
	 */
	public function setSchemaId($id)
	{
		$this->addMetaData('_wc1c_schema_id', $id, true);
	}

	/**
	 * Получение идентификатора схемы через которую была создана категория
	 *
	 * @param string $context Контекст запроса
	 *
	 * @return string|int|false Идентификатор схемы или false
	 */
	public function getSchemaId($context = 'view')
	{
		$data = $this->getMeta('_wc1c_schema_id', true, $context);

		if(isset($data[0]))
		{
			return $data[0];
		}

		return false;
	}

	/**
	 * Установка идентификатора конфигурации через которую создана категория
	 *
	 * @param string|int $id Идентификатор конфигурации
	 *
	 * @return void
	 */
	public function setConfigurationId($id)
	{
		$this->addMetaData('_wc1c_configuration_id', $id, true);
	}

	/**
	 * Получение идентификатора конфигурации через которую была создана категория
	 *
	 * @param string $context Контекст запроса
	 *
	 * @return string|int|false Идентификатор конфигурации или ложь
	 */
	public function getConfigurationId($context = 'view')
	{
		$data = $this->getMeta('_wc1c_configuration_id', true, $context);

		if(isset($data[0]))
		{
			return $data[0];
		}

		return false;
	}

	/**
	 * Назначение идентификатора категории из 1С
	 *
	 * @param string|int $id Идентификатор категории в 1С
	 *
	 * @return void
	 */
	public function assignExternalId($id)
	{
		$this->addMetaData('_wc1c_external_id', $id, false);
	}

	/**
	 * Получение идентификации категорий в 1C
	 *
	 * @param string $context Контекст запроса
	 *
	 * @return string|array|false Идентификатор категории в 1с, либо массив идентификаторов. Ложь в случае отсутствия любого значения.
	 */
	public function getExternalId($context = 'view')
	{
		$data = $this->getMeta('_wc1c_external_id', true, $context);

		if(is_array($data) && isset($data[0]))
		{
			if(count($data) === 1)
			{
				return $data[0];
			}

			return $data;
		}

		return false;
	}

	/**
	 * Имеет ли категория родителя в WooCommerce
	 *
	 * @return boolean
	 */
	public function hasParent(): bool
	{
		return $this->getParentId() !== 0;
	}

	/**
	 * Получение идентификатора категории родителя
	 *
	 * @param string $context Контекст запроса
	 *
	 * @return int|mixed Идентификатор родительской категории в WooCommerce
	 */
	public function getParentId($context = 'view')
	{
		return $this->getProp('parent_id', $context);
	}

	/**
	 * Установка идентификатора категории родителя
	 *
	 * @param int|string $parent_id Идентификатор родительской категории
	 *
	 * @return void
	 */
	public function setParentId($parent_id)
	{
		$this->setProp('parent_id', $parent_id);
	}

	/**
	 * Получение идентификатора изображения категории
	 *
	 * @param string $context Контекст запроса
	 *
	 * @return int|mixed Идентификатор изображения
	 */
	public function getImageId(string $context = 'view')
	{
		return $this->getMeta('thumbnail_id', true, $context);
	}

	/**
	 * Установка идентификатора изображения из галереи
	 *
	 * @param int|string $image_id Идентификатор изображения
	 *
	 * @return void
	 */
	public function setImageId($image_id)
	{
		$this->addMetaData('thumbnail_id', $image_id, true);
	}

	/**
	 * Получение идентификации родительских категорий в 1C
	 *
	 * @param string $context Контекст запроса
	 *
	 * @return string|array|false Идентификатор родительской категории в 1с, либо массив идентификаторов. Ложь в случае отсутствия любого значения.
	 */
	public function getExternalParentId($context = 'view')
	{
		$data = $this->getMeta('_wc1c_external_parent_id', true, $context);

		if( is_array($data) && isset($data[0]))
		{
			if(count($data) === 1)
			{
				return $data[0];
			}

			return $data;
		}

		return false;
	}

	/**
	 * Назначение родительского идентификатора категории в 1C
	 *
	 * @param string|int $id Идентификатор родительской категории в 1С
	 *
	 * @return void
	 */
	public function assignExternalParentId($id)
	{
		$this->addMetaData('_wc1c_external_parent_id', $id, false);
	}

	/**
	 * Имеет ли категория родителя в 1С, при этом родителей может быть множество
	 *
	 * @return boolean
	 */
	public function hasExternalParent(): bool
	{
		return $this->getExternalParentId('view') !== 0;
	}
}
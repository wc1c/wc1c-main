<?php namespace Wc1c\Cml;

defined('ABSPATH') || exit;

use SimpleXMLElement;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Exceptions\RuntimeException;
use Wc1c\Cml\Abstracts\DataAbstract;
use Wc1c\Cml\Entities\Catalog;
use Wc1c\Cml\Entities\Classifier;
use Wc1c\Cml\Entities\Counterparty;
use Wc1c\Cml\Entities\Product;

/**
 * Decoder
 *
 * @package Wc1c\Cml
 */
class Decoder
{
	/**
	 * @var string
	 */
	protected $schema_version;

	/**
	 * @var array
	 */
	protected $types =
	[
		'classifier' => Classifier::class,
		'catalog' => Catalog::class,
		'product' => Product::class,
		'counterparty' => Counterparty::class,
	];

	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * Decoder constructor.
	 *
	 * @param string $schema_version
	 */
	public function __construct(string $schema_version = '')
	{
		if(!empty($schema_version))
		{
			$this->schema_version = $schema_version;
		}
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * @param $type
	 * @param $data
	 *
	 * @return DataAbstract|false|array
	 * @throws Exception
	 */
	public function process($type, $data)
	{
		if(empty($data) || empty($type))
		{
			return false;
		}

		if(!$data instanceof SimpleXMLElement)
		{
			try
			{
				$data = new SimpleXMLElement($data);
			}
			catch(\Exception $e)
			{
				return false;
			}
		}

		switch($type)
		{
			case 'counterparty':
				return $this->decodeCounterparty($data);
			case 'classifier':
				return $this->decodeClassifier($data);
			case 'price_types':
				return $this->decodePriceTypes($data);
			case 'offer':
			case 'product':
				return $this->decodeProduct($data);
			case 'warehouses':
				return $this->decodeWarehouses($data);
			default:
				return false;
		}
	}

	/**
	 * @param SimpleXMLElement $xml
	 *
	 * @return array
	 */
	public function decodePriceTypes(SimpleXMLElement $xml): array
	{
		return $this->parseXmlPriceTypes($xml);
	}

	/**
	 * @param SimpleXMLElement $xml
	 *
	 * @return array
	 */
	public function decodeWarehouses(SimpleXMLElement $xml): array
	{
		return $this->parseXmlWarehouses($xml);
	}

	/**
	 * @param SimpleXMLElement $xml
	 *
	 * @return Classifier|false
	 * @throws Exception
	 */
	public function decodeClassifier(SimpleXMLElement $xml)
	{
		$data['id'] = (string)$xml->Ид;
		$data['name'] = (string)$xml->Наименование;
		$data['description'] = $xml->Описание ? (string)$xml->Описание : '';
		$data['owner'] = $this->decodeCounterparty($xml->Владелец);

		/**
		 * Группы
		 * Определяет иерархическую структуру номенклатуры
		 *
		 * cml:Группа
		 */
		$data['groups'] = $xml->Группы ? $this->parseXmlClassifierGroups($xml->Группы) : [];

		/**
		 * Свойства
		 * Содержит коллекцию свойств, значения которых можно или нужно указать ДЛЯ ВСЕХ товаров в
		 * каталоге, пакете предложений, документах
		 *
		 * cml:Свойство
		 */
		$data['properties'] = $xml->Свойства ? $this->parseXmlClassifierProperties($xml->Свойства) : [];

		/**
		 * Типы цен
		 * Определяет типы цен, которые могут быть использованы при формировании пакета коммерческих предложений
		 *
		 * cml:ТипЦены
		 */
		$data['price_types'] = $xml->ТипыЦен ? $this->parseXmlPriceTypes($xml->ТипыЦен) : [];

		/**
		 * Единицы измерения
		 */
		$data['units'] = $xml->ЕдиницыИзмерения ? $this->parseXmlUnits($xml->ЕдиницыИзмерения) : [];

		/**
		 * Категории
		 * Определяет иерархическую структуру номенклатуры
		 *
		 * cml:Категория
		 */
		$data['categories'] = $xml->Категории ? $this->parseXmlClassifierCategories($xml->Категории) : [];

		/**
		 * Склады
		 */
		$data['warehouses'] = $xml->Склады ? $this->parseXmlWarehouses($xml->Склады) : [];

		try
		{
			$classifier = new Classifier($data);
		}
		catch(\Exception $e)
		{
			return false;
		}

		return $classifier;
	}

	/**
	 * @param SimpleXMLElement $xml
	 *
	 * @return false|Counterparty
	 */
	public function decodeCounterparty(SimpleXMLElement $xml)
	{
		$data['id'] = (string)$xml->Ид;
		$data['name'] = (string)$xml->Наименование;
		$data['full_name'] = (string)$xml->ПолноеНаименование;
		$data['inn'] = $xml->ИНН ? (string)$xml->ИНН : '';

		try
		{
			$counterparty =  new Counterparty($data);
		}
		catch(\Exception $e)
		{
			return false;
		}

		return $counterparty;
	}

	/**
	 * @param $xml
	 *
	 * @return false|Product
	 */
	public function decodeProduct($xml)
	{
		try
		{
			$data = $this->parseXmlProduct($xml);
		}
		catch(\Exception $e)
		{
			return false;
		}

		try
		{
			$product = new Product($data);
		}
		catch(\Exception $e)
		{
			return false;
		}

		return $product;
	}

	/**
	 * Парсинг групп из классификатора
	 *
	 * @throws Exception
	 *
	 * @param $xml_data
	 * @param string|false $parent_id
	 * @param array $groups
	 *
	 * @return array Все найденные в классификаторе группы
	 */
	private function parseXmlClassifierGroups($xml_data, $parent_id = false, &$groups = []): array
	{
		foreach($xml_data->Группа as $xml_group)
		{
			$id = (string)$xml_group->Ид;

			try
			{
				$groups[$id] = $this->parseXmlClassifierGroupsItem($xml_group, $parent_id);
			}
			catch(Exception $e)
			{
				continue;
			}

			if($xml_group->Группы)
			{
				$this->parseXmlClassifierGroups($xml_group->Группы, $id, $groups);
			}
		}

		return $groups;
	}

	/**
	 * Парсинг категорий из классификатора
	 *
	 * @param $xml_data
	 * @param string|false $parent_id
	 * @param array $categories
	 *
	 * @return array Все найденные в классификаторе категории
	 *@throws Exception
	 *
	 */
	private function parseXmlClassifierCategories($xml_data, $parent_id = false, &$categories = []): array
	{
		foreach($xml_data->Категория as $xml_category)
		{
			$id = (string)$xml_category->Ид;

			try
			{
				$categories[$id] = $this->parseXmlClassifierCategoriesItem($xml_category, $parent_id);
			}
			catch(Exception $e)
			{
				continue;
			}

			if($xml_category->Категории)
			{
				$this->parseXmlClassifierCategories($xml_category->Категории, $id, $categories);
			}
		}

		return $categories;
	}

	/**
	 * @param $xml_group
	 * @param string|false $parent_guid
	 *
	 * @return array
	 */
	private function parseXmlClassifierGroupsItem($xml_group, $parent_guid = false): array
	{
		$group_guid = (string)$xml_group->Ид;
		$group_name = (string)$xml_group->Наименование;

		if($group_guid === '' || $group_name === '')
		{
			throw new RuntimeException('Group is not valid.');
		}

		$data =
		[
			'name' => $group_name,
			'id' => $group_guid,
			'parent_id' => $parent_guid ?: false,
			'version' => $xml_group->НомерВерсии ? (string)$xml_group->НомерВерсии : '',
		];

		$data['description'] = '';
		if($xml_group->Описание)
		{
			$data['description'] = (string)$xml_group->Описание;
		}

		$data['image'] = '';
		if($xml_group->Картинка)
		{
			$data['image'] = (string)$xml_group->Картинка;
		}

		$data['mark_delete'] = 'no';
		if((string)$xml_group->ПометкаУдаления === 'true')
		{
			$data['mark_delete'] = 'yes';
		}

		$properties = [];
		if($xml_group->Свойства)
		{
			foreach($xml_group->Свойства->Ид as $property)
			{
				$properties[] = (string)$property;
			}
		}

		$data['properties'] = $properties;

		return $data;
	}

	/**
	 * @param $xml_category
	 * @param string|false $parent_guid
	 *
	 * @return array
	 */
	private function parseXmlClassifierCategoriesItem($xml_category, $parent_guid = false): array
	{
		$category_guid = (string)$xml_category->Ид;
		$category_name = (string)$xml_category->Наименование;

		if($category_guid === '' || $category_name === '')
		{
			throw new RuntimeException('Category is not valid.');
		}

		$data =
		[
			'name' => $category_name,
			'id' => $category_guid,
			'parent_id' => $parent_guid ?: false,
			'version' => $xml_category->НомерВерсии ? (string)$xml_category->НомерВерсии : '',
		];

		$data['description'] = '';
		if($xml_category->Описание)
		{
			$data['description'] = (string)$xml_category->Описание;
		}

		$data['image'] = '';
		if($xml_category->Картинка)
		{
			$data['image'] = (string)$xml_category->Картинка;
		}

		$data['mark_delete'] = 'no';
		if((string)$xml_category->ПометкаУдаления === 'true')
		{
			$data['mark_delete'] = 'yes';
		}

		$properties = [];
		if($xml_category->Свойства)
		{
			foreach($xml_category->Свойства->Ид as $property)
			{
				$properties[] = (string)$property;
			}
		}

		$data['properties'] = $properties;

		return $data;
	}

	/**
	 * Обработка свойств классификатора
	 *
	 * @param $xml_data
	 *
	 * @return array
	 */
	private function parseXmlClassifierProperties($xml_data): array
	{
		$properties = [];

		if(isset($xml_data->Свойство))
		{
			$properties_xml_data = $xml_data->Свойство;
		}
		elseif(isset($xml_data->СвойствоНоменклатуры))
		{
			$properties_xml_data = $xml_data->СвойствоНоменклатуры;
		}

		if(empty($properties_xml_data))
		{
			return $properties;
		}

		foreach($properties_xml_data as $property_xml_data)
		{
			try
			{
				$property_data = $this->parseXmlClassifierPropertiesItem($property_xml_data);
				$properties[$property_data['id']] = $property_data;
			}
			catch(\Throwable $e)
			{
				continue;
			}
		}

		return $properties;
	}

	/**
	 * Свойство
	 *
	 * @param $xml_property
	 *
	 * @return array
	 */
	private function parseXmlClassifierPropertiesItem($xml_property): array
	{
		/**
		 * Наименование свойства в классификаторе
		 */
		$property_data['name'] = htmlspecialchars(trim((string)$xml_property->Наименование));

		/**
		 * Идентификатор свойства в классификаторе
		 */
		$property_data['id'] = (string)$xml_property->Ид;

		/**
		 * Описание свойства, например, для чего оно предназначено
		 */
		$property_data['description'] = isset($xml_property->Описание) ? htmlspecialchars(trim((string)$xml_property->Описание)) : '';

		/**
		 * Обязательное
		 */
		$property_data['required'] = 'no';
		if(isset($xml_property->Обязательное))
		{
			$property_data['required'] = (string)$xml_property->Обязательное === 'true' ? 'yes' : 'no';
		}

		/**
		 * Множественное
		 */
		$property_data['multiple'] = 'no';
		if(isset($xml_property->Множественное))
		{
			$property_data['multiple'] = (string)$xml_property->Множественное === 'true' ? 'yes' : 'no';
		}

		/**
		 * Тип значений
		 *
		 * Один из следующих типов: Строка (по умолчанию), Число,  ДатаВремя, Справочник
		 */
		$property_data['values_type'] = 'Строка';
		if(isset($xml_property->ТипЗначений))
		{
			$property_data['values_type'] = (string)$xml_property->ТипЗначений;
		}
		// 2.04.1CBitrix
		if(isset($xml_property->ТипыЗначений->ТипЗначений->Тип))
		{
			$property_data['values_type'] = (string)$xml_property->ТипыЗначений->ТипЗначений->Тип;
		}

		/**
		 * Варианты значений
		 *
		 * Содержит коллекцию вариантов значений свойства.
		 * Если варианты указаны, то при указании значений данного свойства для товаров должны использоваться значения СТРОГО из данного списка
		 */
		$property_values_data = [];
		$property_data['values_variants'] = $property_values_data;
		if($property_data['values_type'] === 'Справочник')
		{
			if(isset($xml_property->ВариантыЗначений->Справочник))
			{
				foreach($xml_property->ВариантыЗначений->Справочник as $value)
				{
					$property_values_data[(string)$value->ИдЗначения] = htmlspecialchars(trim((string)$value->Значение));
				}
			}
			// 2.04.1CBitrix
			if(isset($xml_property->ТипыЗначений->ТипЗначений))
			{
				foreach($xml_property->ТипыЗначений->ТипЗначений->ВариантыЗначений as $value)
				{
					$property_values_data[(string)$value->Ид] = htmlspecialchars(trim((string)$value->Значение));
				}
			}

			$property_data['values_variants'] = $property_values_data;
		}

		/**
		 * Свойство для товаров
		 *
		 * Свойство может (или должно) использоваться при описании товаров в каталоге, пакете предложений, документах
		 */
		$property_data['use_products'] = 'no';
		if(isset($xml_property->ДляТоваров))
		{
			$property_data['use_products'] = (string)$xml_property->ДляТоваров === 'true' ? 'yes' : 'no';
		}

		/**
		 * Для предложений
		 *
		 * Свойство может (должно) использоваться при описании товара в пакете предложений. Например: гарантийный срок, способ доставки
		 */
		$property_data['use_offers'] = 'no';
		if(isset($xml_property->ДляПредложений))
		{
			$property_data['use_offers'] = (string)$xml_property->ДляПредложений === 'true' ? 'yes' : 'no';
		}

		/**
		 * Для документов
		 *
		 * Свойство может (должно) использоваться при описании товара в документе. Например: серийный номер
		 */
		if(isset($xml_property->ДляДокументов))
		{
			$property_data['use_documents'] = (string)$xml_property->ДляПредложений === 'true' ? 'yes' : 'no';
		}

		/**
		 * Внешний
		 */
		$property_data['external']  = 'no';
		if(isset($xml_property->Внешний))
		{
			$property_data['external'] = (string)$xml_property->Внешний === 'true' ? 'yes' : 'no';
		}

		/**
		 * Информационное
		 */
		$property_data['informational']  = 'no';
		if(isset($xml_property->Информационное))
		{
			$property_data['informational'] = (string)$xml_property->Информационное === 'true' ? 'yes' : 'no';
		}

		/**
		 * Маркер удаления
		 */
		$property_data['mark_delete']  = 'no';
		if(isset($xml_property->ПометкаУдаления))
		{
			$property_data['mark_delete'] = (string)$xml_property->ПометкаУдаления === 'true' ? 'yes' : 'no';
		}

		/**
		 * Номер версии
		 */
		$property_data['version']  = '';
		if(isset($xml_property->НомерВерсии))
		{
			$property_data['version'] = (string)$xml_property->НомерВерсии;
		}

		return $property_data;
	}

	/**
	 * Разбор одной позиции продукта
	 *
	 * @param $xml_product_data
	 *
	 * @return array
	 * @throws Exception
	 */
	private function parseXmlProduct($xml_product_data): array
	{
		if(!isset($xml_product_data->Ид))
		{
			throw new Exception('$product_xml_data->Ид empty.');
		}

		$product_data = $this->parseXmlProductId($xml_product_data->Ид);

		/**
		 * Наименование товара
		 */
		$product_data['name'] = isset($xml_product_data->Наименование) ? (string)$xml_product_data->Наименование : '';

		/**
		 * Артикул
		 */
		$product_data['sku'] = isset($xml_product_data->Артикул) ? (string)$xml_product_data->Артикул : '';

		/**
		 * Штрихкод
		 */
		$product_data['ean'] = isset($xml_product_data->Штрихкод) ? (string)$xml_product_data->Штрихкод : '';

		/*
		 * Базовая единица
		 *
		 * Имя базовой единицы измерения товара по ОКЕИ. В документах и коммерческих предложениях может быть указана другая единица измерения,
		 * но при этом обязательно указывается коэффициент пересчета количества в базовую единицу товара.
		 */
		$product_data['base_unit'] = isset($xml_product_data->БазоваяЕдиница) ? $this->parseXmlProductBaseUnit($xml_product_data->БазоваяЕдиница) : [];

		/**
		 * Идентификатор товара у контрагента (идентификатор товара в системе контрагента)
		 * cml:ИдентификаторГлобальныйТип
		 */
		$product_data['counterparty_product_guid'] = isset($xml_product_data->ИдТовараУКонтрагента) ? (string)$xml_product_data->ИдТовараУКонтрагента : '';

		/**
		 * Группы товара
		 *
		 * Содержит идентификаторы групп, которым принадлежит данный товар в указанном классификаторе.
		 */
		$product_data['classifier_groups'] = isset($xml_product_data->Группы) ? $this->parseXmlProductGroups($xml_product_data->Группы) : [];

		/**
		 * Категории товара
		 *
		 * Содержит идентификаторы категорий, которым принадлежит данный товар в указанном классификаторе.
		 */
		$product_data['classifier_categories'] = isset($xml_product_data->Категория) ? $this->parseXmlProductCategories($xml_product_data->Категория) : [];

		/**
		 * Описание товара
		 */
		$description =isset($xml_product_data->Описание) ? htmlspecialchars(trim((string)$xml_product_data->Описание)) : '';
		$product_data['description'] = str_replace(["\r\n", "\r", "\n"], "<br />", $description);

		/**
		 * Изображения
		 *
		 * Имя файла картинки для номенклатурной позиции. Файлы картинок могут поставляться отдельно
		 * от передаваемого файла с коммерческой информацией
		 */
		$product_data['images'] = isset($xml_product_data->Картинка) ? $this->parseXmlProductImages($xml_product_data->Картинка) : [];

		// CML 2.04
		if(isset($xml_product_data->ОсновнаяКартинка))
		{
			$product_data['images'] = $this->parseXmlProductImages($xml_product_data->ОсновнаяКартинка);
		}

		/***************************************************************************************************************************************
		 * Дополнительные данные
		 *------------------------------------------------------------------------------------------------------------------------------------*/

		/**
		 * Страна
		 */
		$product_data['country'] = isset($xml_product_data->Страна) ? (string)$xml_product_data->Страна : '';

		/**
		 * Торговая марка
		 */
		$product_data['trademark'] = isset($xml_product_data->ТорговаяМарка) ? (string)$xml_product_data->ТорговаяМарка : '';

		/*
		 * Владелец торговой марки
		 */
		$product_data['trademark_owner'] = isset($xml_product_data->ВладелецТорговойМарки) ? $this->decodeCounterparty($xml_product_data->ВладелецТорговойМарки) : '';

		/*
		 * Производитель todo: вынести разбор в отдельный метод и добавить try catch
		 *
		 * Содержит описание страны, непосредственно изготовителя и торговой марки товара.
		 * Страна - строка
		 * ТорговаяМарка - строка
		 * ВладелецТорговойМарки - Контрагент
		 * Изготовитель - Контрагент
		 */
		$product_data['manufacturer'] = [];
		if(isset($xml_product_data->Изготовитель))
		{
			$product_data['manufacturer']['name'] = trim((string)$xml_product_data->Изготовитель->Наименование);
			$product_data['manufacturer']['id'] = trim((string)$xml_product_data->Изготовитель->Ид);
		}
		elseif(isset($xml_product_data->Производитель))
		{
			$product_data['manufacturer']['name'] = trim((string)$xml_product_data->Производитель);
		}

		/**
		 * Значения свойств
		 *
		 * Описывает значения свойств (характеристик) номенклатурной позиции в соответствии с указанным классификатором.
		 * Если классификатор не указан, то включать данный элемент не имеет смысла.
		 */
		$product_data['property_values'] = isset($xml_product_data->ЗначенияСвойств) ? $this->parseXmlProductPropertyValues($xml_product_data->ЗначенияСвойств) : [];

		/*
		 * Налоговые ставки продукта
		 */
		$product_data['taxes'] = isset($xml_product_data->СтавкиНалогов) ? $this->parseXmlProductTaxes($xml_product_data->СтавкиНалогов) : [];

		/*
		 * Акцизы
		 */
		// todo: обработка в отдельном методе с try catch

		/*
		 * Комплектующие
		 * Для изделий, содержащих комплектующие
		 * Комплектующее - Элементы типа «Товар» - определяют комплектующие составных товаров - наборов.
		 */
		// todo: обработка в отдельном методе с try catch

		/*
		 * Аналоги
		 * Аналоги товара, например для медикаментов другие лекарства, заменяющие данное
		 * Аналог - Элементы типа «Товар» - определяют аналогичные товары, например, в другом каталоге
		 */
		// todo: обработка в отдельном методе с try catch

		/**
		 * Характеристики товара. Товар с разными характеристиками может иметь разную цену и остатки.
		 */
		$product_data['characteristics'] = $xml_product_data->ХарактеристикиТовара ? $this->parseXmlProductCharacteristics($xml_product_data->ХарактеристикиТовара) : [];

		/**
		 * Значения реквизитов товара
		 * Определяет значение произвольного реквизита документа
		 */
		$requisites_values = false;
		if(isset($xml_product_data->ЗначениеРеквизита)) // cml 2.05-
		{
			$requisites_values = $xml_product_data->ЗначениеРеквизита;
		}
		elseif(isset($xml_product_data->ЗначенияРеквизитов)) // cml 2.05+
		{
			$requisites_values = $xml_product_data->ЗначенияРеквизитов;
		}
		$product_data['requisites'] = $requisites_values ? $this->parseXmlProductRequisites($requisites_values) : [];

		/***************************************************************************************************************************************
		 * Предложения
		 *------------------------------------------------------------------------------------------------------------------------------------*/

		/**
		 * Цены
		 */
		$product_data['prices'] = isset($xml_product_data->Цены) ? $this->parseXmlProductPrice($xml_product_data->Цены) : [];

		/**
		 * Количество предлагаемого товара. Например, может быть указан остаток на складе.
		 */
		$product_data['quantity'] = 0;
		if($xml_product_data->Остатки || $xml_product_data->Количество || $xml_product_data->Склад)
		{
			$product_data['quantity'] = $this->parseXmlProductQuantity($xml_product_data);
		}

		/**
		 * Складские остатки
		 * Количество предлагаемого продукта по складам
		 */
		$product_data['warehouses'] = [];
		if($xml_product_data->Склад || $xml_product_data->Остатки)
		{
			$product_data['warehouses'] = $this->parseXmlProductWarehouses($xml_product_data);
		}

		/***************************************************************************************************************************************
		 * Прочие данные
		 *------------------------------------------------------------------------------------------------------------------------------------*/

		/**
		 * Полное наименование
		 */
		$product_data['full_name'] = '';
		if($xml_product_data->ПолноеНаименование)
		{
			$product_data['full_name'] = htmlspecialchars(trim((string)$xml_product_data->ПолноеНаименование));
		}
		if(isset($product_data['requisites']['Полное наименование']))
		{
			$product_data['full_name'] = $product_data['requisites']['Полное наименование']['value'];
		}

		/**
		 * Модель
		 */
		$product_data['model'] = isset($xml_product_data->Модель) ? (string)$xml_product_data->Модель : '';

		/***************************************************************************************************************************************
		 * Технические данные
		 *------------------------------------------------------------------------------------------------------------------------------------*/

		/**
		 * Версия продукта
		 */
		$product_data['version'] = isset($xml_product_data->НомерВерсии) ? (string)$xml_product_data->НомерВерсии : '';

		/**
		 * Пометка товара на удаление
		 */
		$product_data['delete_mark'] = 'no';
		if(isset($xml_product_data->ПометкаУдаления))
		{
			$product_data['delete_mark'] = (string)$xml_product_data->ПометкаУдаления === 'true' ? 'yes' : 'no';
		}
		/* УНФ */
		if(isset($xml_product_data->Статус))
		{
			$product_data['delete_mark'] = (string)$xml_product_data->Статус === 'Удален' ? 'yes' : 'no';
		}
		/* 2.04.1CBitrix */
		if(isset($xml_product_data->ПомеченНаУдаление))
		{
			$product_data['delete_mark'] = (string)$xml_product_data->ПомеченНаУдаление === 'true' ? 'yes' : 'no';
		}

		/**
		 * Спецификация
		 */
		/* 2.04.1CBitrix */
		$product_data['specification'] = '';
		if(isset($xml_product_data->Спецификация))
		{
			$product_data['specification'] = htmlspecialchars(trim((string)$xml_product_data->Спецификация));
		}

		return $product_data;
	}

	/**
	 * Разбор характеристик с исключением дублей
	 *
	 * @param $xml_data
	 *
	 * @return array
	 * @throws Exception
	 */
	private function parseXmlProductCharacteristics($xml_data): array
	{
		if(!isset($xml_data->ХарактеристикаТовара))
		{
			throw new Exception('$xml_data->ХарактеристикаТовара is empty.');
		}

		$characteristics = [];

		// Уточняет характеристики поставляемого товара. Товар с разными характеристиками может иметь разную цену и остатки
		foreach($xml_data->ХарактеристикаТовара as $product_feature)
		{
			/*
			 * Идентификатор характеристики
			 *
			 * cml:НаименованиеТип
			 * 2.06+
			 */
			$id = '';
			if(isset($product_feature->Ид))
			{
				$id = trim(htmlspecialchars((string) $product_feature->Ид));
			}

			/*
			 * Наименование характеристики
			 *
			 * cml:НаименованиеТип
			 */
			$name = trim(htmlspecialchars((string) $product_feature->Наименование));

			/*
			 * Значение характеристики
			 *
			 * cml:ЗначениеТип
			 */
			$value = trim(htmlspecialchars((string) $product_feature->Значение));

			/*
			 * Собираем без дублей в имени
			 */
			if(isset($characteristics[$name]))
			{
				$old = $characteristics[$name]['value'];

				if(is_array($old))
				{
					$old[] = $value;
				}
				else
				{
					$old[] = $characteristics[$name]['value'];
					$old[] = $value;
				}

				continue;
			}

			$characteristics[$name] =
			[
				'id' => $id,
				'name' => $name,
				'value' => $value
			];
		}

		return $characteristics;
	}

	/**
	 * Обработка групп продукта
	 *
	 * @param $xml_data
	 *
	 * @return array Массив GUID (идентификаторов групп)
	 */
	private function parseXmlProductGroups($xml_data): array
	{
		$result = [];

		foreach($xml_data->Ид as $group_guid)
		{
			/**
			 * Идентификатор группы товаров в классификаторе
			 * cml:ИдентификаторГлобальныйТип
			 */
			$result[] = (string)$group_guid;
		}

		return $result;
	}

	/**
	 * Обработка категорий продукта
	 *
	 * @param $xml_data
	 *
	 * @return array Массив GUID (идентификаторов категорий)
	 */
	private function parseXmlProductCategories($xml_data): array
	{
		$result = [];

		foreach($xml_data as $category_guid)
		{
			/**
			 * Идентификатор категории товаров в классификаторе
			 * cml:ИдентификаторГлобальныйТип
			 */
			$result[] = (string)$category_guid;
		}

		return $result;
	}

	/**
	 * @param $product_xml_data_id
	 *
	 * @return array
	 */
	private function parseXmlProductId($product_xml_data_id): array
	{
		$product_guid = explode("#", (string)$product_xml_data_id);
		$product_data_id['id'] = $product_guid[0];
		$product_data_id['characteristic_id'] = $product_guid[1] ?? '';

		return $product_data_id;
	}

	/**
	 * Разбор изображений
	 *
	 * @param $xml_data
	 *
	 * @return array
	 */
	private function parseXmlProductImages($xml_data): array
	{
		$images = [];

		foreach($xml_data as $image)
		{
			$image = (string)$image;

			if(empty($image))
			{
				continue;
			}

			$images[] = $image;
		}

		return $images;
	}

	/**
	 * Разбор цены продукта
	 *
	 * @param $xml_product_price_data
	 *
	 * @return array
	 */
	private function parseXmlProductPrice($xml_product_price_data): array
	{
		$data_prices = [];

		foreach($xml_product_price_data->Цена as $price_data)
		{
			/*
			 * Идентификатор типа цены
			 *
			 * cml:ИдентификаторГлобальныйТип
			 */
			$price_type_guid = (string)$price_data->ИдТипаЦены;

			/*
			 * Представление цены так, как оно отображается в прайс-листе. Например: 10у.е./за 1000 шт
			 *
			 * cml:НаименованиеТип
			 */
			$price_presentation = $price_data->Представление ? (string)$price_data->Представление : '';

			/*
			 * Цена за единицу товара
			 *
			 * cml:СуммаТип
			 */
			$price = $price_data->ЦенаЗаЕдиницу ? (float)$price_data->ЦенаЗаЕдиницу : 0;

			/*
			 * Коэффициент
			 */
			$rate = $price_data->Коэффициент ? (float)$price_data->Коэффициент : 1;

			/*
			 * Валюта
			 * Код валюты по международному классификатору валют (ISO 4217).
			 * Если не указана, то используется валюта установленная для данного типа цен
			 *
			 * cml:ВалютаТип
			 */
			$currency = $price_data->Валюта ? (string)$price_data->Валюта : 'RUB';

			/*
			 * Минимальное количество товара в указанных единицах, для которого действует данная цена.
			 *
			 * cml:КоличествоТип
			 */
			$min_quantity = $price_data->МинКоличество ? (string)$price_data->МинКоличество : '0';

			/*
			 * todo: обрабатывать правильно
			 *
			 * cml:ЕдиницаИзмерения
			 */
			$unit = $price_data->Единица ? (string)$price_data->Единица : '';

			/**
			 * Собираем итог
			 */
			$data_prices[$price_type_guid] =
			[
				'price' => $price,
				'price_type_id' => $price_type_guid,
				'price_rate' => $rate,
				'price_currency' => $currency,
				'price_presentation' => $price_presentation,
				'price_unit' => $unit,
				'min_quantity' => $min_quantity,
			];
		}

		return $data_prices;
	}

	/**
	 * Разбор значений свойств товара
	 *
	 * Описывает значения свойств (характеристик) номенклатурной позиции в соответствии с указанным классификатором.
	 * Если классификатор не указан, то включать данный элемент не имеет смысла.
	 *
	 * @param $xml
	 *
	 * @return array
	 */
	private function parseXmlProductPropertyValues($xml): array
	{
		$product_properties_values_data = [];

		foreach($xml->ЗначенияСвойства as $xml_property_values_data)
		{
			if(!isset($xml_property_values_data->Ид))
			{
				continue;
			}

			/**
			 * Глобальный идентификатор
			 */
			$property_values_data['id'] = (string)$xml_property_values_data->Ид;

			/**
			 * Наименование свойства может быть, а может и нет
			 *
			 * cml:НаименованиеТип
			 */
			$property_values_data['name'] = $xml_property_values_data->Наименование ? (string)$xml_property_values_data->Наименование : '';

			/**
			 * Значение свойства может быть значением, либо ссылкой на значение справочника классификатора.
			 */
			$property_values_data['value'] = $xml_property_values_data->Значение ? (string)$xml_property_values_data->Значение : '';

			/**
			 * Add to all
			 */
			$product_properties_values_data[$property_values_data['id']] = $property_values_data;
		}

		return $product_properties_values_data;
	}

	/**
	 * Разбор остатков продукта
	 *
	 * @param $xml_data
	 *
	 * @return float|int
	 */
	private function parseXmlProductQuantity($xml_data)
	{
		$quantity = 0;

		/*
		 * CML < 2.08
		 */
		if($xml_data->Количество)
		{
			$quantity = (float)$xml_data->Количество;
		}
		elseif($xml_data->Склад)
		{
			foreach ($xml_data->Склад as $product_quantity)
			{
				$quantity += (float)$product_quantity['КоличествоНаСкладе'];
			}
		}

		/*
		 * CML 2.09, 2.10
		 */
		if($xml_data->Остатки)
		{
			foreach($xml_data->Остатки->Остаток as $product_quantity)
			{
				// Если нет складов или общий остаток предложения
				if($product_quantity->Количество)
				{
					$quantity = (float)$product_quantity->Количество;
				}
				elseif($product_quantity->Склад)
				{
					foreach($product_quantity->Склад as $quantity_warehouse)
					{
						$quantity += (float)$quantity_warehouse->Количество;
					}
				}
			}
		}

		return $quantity;
	}

	/**
	 * Разбор реквизитов продукта
	 *
	 * @param $xml_data
	 *
	 * @return array
	 */
	private function parseXmlProductRequisites($xml_data): array
	{
		$requisites_data = [];

		foreach($xml_data->ЗначениеРеквизита as $requisite)
		{
			$name = (string)$requisite->Наименование;
			$value = (string)$requisite->Значение;

			if(empty($value))
			{
				continue;
			}

			if(isset($requisites_data[$name]))
			{
				$old_value = $requisites_data[$name]['value'];

				if(!is_array($old_value))
				{
					$requisites_data[$name]['value'] = [];
					$requisites_data[$name]['value'][] = $old_value;
				}
				$requisites_data[$name]['value'][] = $value;

				continue;
			}

			$requisites_data[$name] =
			[
				'name' => $name,
				'value' => $value
			];
		}

		return $requisites_data;
	}

	/**
	 * Разбор налоговых ставок продукта
	 *
	 * @param $xml_data
	 *
	 * @return array
	 */
	private function parseXmlProductTaxes($xml_data): array
	{
		$taxes = [];

		foreach($xml_data->СтавкаНалога as $product_tax)
		{
			// Вид налога. Например, НДС
			$name = trim((string)$product_tax->Наименование);

			// Ставка налога
			$rate = (string)$product_tax->Ставка;

			// final
			$taxes[$name] = $rate;
		}

		return $taxes;
	}

	/**
	 * Разбор конкретного свойства продукта
	 *
	 * @throws
	 *
	 * @param $xml_property_data
	 *
	 * @return array
	 */
	private function parse_xml_product_values_properties_single($xml_property_data)
	{
		/**
		 * Идентификатор свойства в классификаторе товаров
		 *
		 * cml:ИдентификаторГлобальныйТип
		 */
		$property_data['property_guid'] = (string)$xml_property_data->Ид;

		/**
		 * Загрузка свойств только при присутствии в справочнике классификатора
		 */
		if(!isset($classifier_properties[$property_data['property_guid']]))
		{
			throw new Exception('parse_xml_product_values_properties_single: property not found in classifier by property guid ' . $property_data['property_guid']);
		}

		/**
		 * Наименование свойства
		 * может быть, а может и нет
		 *
		 * cml:НаименованиеТип
		 */
		$property_data['property_name'] = htmlspecialchars(trim((string)$xml_property_data->Наименование));
		if($property_data['property_name'] === '')
		{
			$property_data['property_name'] = $classifier_properties[$property_data['property_guid']]['property_name'];
		}

		/**
		 * Значение свойства
		 * Может быть значением, либо ссылкой на значение справочника классификатора.
		 */
		$property_data['property_value'] = htmlspecialchars(trim((string)$xml_property_data->Значение));

		/**
		 * Если значение свойства является идентификатором значения свойства из справочника
		 */
		if($classifier_properties[$property_data['property_guid']]['property_values_type'] === 'Справочник')
		{
			if(isset($classifier_properties[$property_data['property_guid']]['property_values_data'][$property_data['property_value']]))
			{
				$property_data['property_value'] = $classifier_properties[$property_data['property_guid']]['property_values_data'][$property_data['property_value']];
			}
		}
		if($classifier_properties[$property_data['property_guid']]['property_values_type'] === 'Строка')
		{
			$property_data['property_value'] = htmlspecialchars(trim((string)$xml_property_data->Значение));
		}
		if($classifier_properties[$property_data['property_guid']]['property_values_type'] === 'Число')
		{
			$property_data['property_value'] = htmlspecialchars(trim((string)$xml_property_data->Значение));
		}

		return $property_data;
	}

	/**
	 * Разбор складов продукта
	 *
	 * @param $xml_data
	 *
	 * @return array
	 */
	private function parseXmlProductWarehouses($xml_data): array
	{
		$warehouses = [];

		/*
		 * CML < 2.08
		 */
		if($xml_data->Склад)
		{
			foreach($xml_data->Склад as $warehouse)
			{
				$warehouses[(string)$warehouse['ИдСклада']] =
				[
					'id' => (string)$warehouse['ИдСклада'],
					'quantity' => (float)$warehouse['КоличествоНаСкладе']
				];
			}
		}

		/*
		 * CML 2.09, 2.10
		 */
		if($xml_data->Остатки) // todo: test
		{
			foreach($xml_data->Остатки->Остаток as $product_quantity)
			{
				if($product_quantity->Склад)
				{
					foreach($product_quantity->Склад as $warehouse)
					{
						$warehouses[(string)$warehouse->ИдСклада] =
						[
							'id' => (string)$warehouse->ИдСклада,
							'quantity' => (float)$warehouse->Количество
						];
					}
				}
			}
		}

		return $warehouses;
	}

	/**
	 * Разбор складов
	 *
	 * @param $xml_data
	 *
	 * @return array
	 */
	private function parseXmlWarehouses($xml_data): array
	{
		$data = [];

		foreach($xml_data->Склад as $xml_warehouse)
		{
			$id = (string)$xml_warehouse->Ид;
			$name = trim((string)$xml_warehouse->Наименование);
			$description = trim((string)$xml_warehouse->Комментарий);

			// todo: Адрес

			// todo: Контакты

			$data[$id] =
			[
				'id' => $id,
				'name' => $name,
				'description' => $description
			];
		}

		return $data;
	}

	/**
	 * Разбор типов цен
	 *
	 * @param $xml_data
	 *
	 * @return array
	 */
	public function parseXmlPriceTypes($xml_data): array
	{
		$data = [];

		foreach($xml_data->ТипЦены as $price_type)
		{
			$guid = (string)$price_type->Ид;
			$name = trim((string)$price_type->Наименование);
			$description = trim((string)$price_type->Описание);
			$code = $price_type->Код ?: '';

			/*
			 * Валюта
			 * Код валюты по международному классификатору валют (ISO 4217).
			 * Если не указана, то используется валюта установленная для данного типа цен
			 *
			 * cml:ВалютаТип
			 */
			$currency = $price_type->Валюта ? (string)$price_type->Валюта : 'RUB';

			//todo: cml:Налог

			$data[$guid] =
			[
				'guid' => $guid,
				'name' => $name,
				'currency' => $currency,
				'code' => (string)$code,
				'description' => $description
			];
		}

		return $data;
	}

	/**
	 * Разбор единиц измерений
	 *
	 * @param $xml_data
	 *
	 * @return array
	 */
	private function parseXmlUnits($xml_data): array
	{
		$data = [];

		foreach($xml_data->ЕдиницаИзмерения as $xml_data_value)
		{
			$data[trim((string)$xml_data_value->Код)] =
			[
				'code' => trim((string)$xml_data_value->Код),
				'full_name' => trim((string)$xml_data_value->НаименованиеПолное),
				'short_name_intl' => trim((string)$xml_data_value->МеждународноеСокращение),
				'short_name' => trim((string)$xml_data_value->НаименованиеКраткое),
			];
		}

		return $data;
	}

	/**
	 * Разбор базовой единицы измерения продукта
	 *
	 * @param $xml_data
	 *
	 * @return array
	 */
	public function parseXmlProductBaseUnit($xml_data): array
	{
		return
		[
			'code' => (string)$xml_data['Код'],
			'full_name' => $xml_data['НаименованиеПолное'] ? (string)$xml_data['НаименованиеПолное'] : '',
			'short_name' => $xml_data['НаименованиеКраткое'] ? (string)$xml_data['НаименованиеКраткое'] : '',
			'short_name_intl' => $xml_data['МеждународноеСокращение'] ? (string)$xml_data['МеждународноеСокращение'] : '',
		];
	}
}
<?php namespace Wc1c\Main\Schemas\Productscml;

defined('ABSPATH') || exit;

use XMLReader;
use Wc1c\Main\Wc\Products\AttributeProduct;
use Wc1c\Main\Cml\Contracts\ClassifierDataContract;
use Wc1c\Main\Cml\Contracts\ProductDataContract;
use Wc1c\Main\Cml\Decoder;
use Wc1c\Main\Cml\Entities\Catalog;
use Wc1c\Main\Cml\Entities\OffersPackage;
use Wc1c\Main\Cml\Reader;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Schemas\Abstracts\SchemaAbstract;
use Wc1c\Main\Wc\Contracts\AttributeContract;
use Wc1c\Main\Wc\Contracts\AttributesStorageContract;
use Wc1c\Main\Wc\Contracts\CategoriesStorageContract;
use Wc1c\Main\Wc\Contracts\ImagesStorageContract;
use Wc1c\Main\Wc\Contracts\ProductContract;
use Wc1c\Main\Wc\Entities\Attribute;
use Wc1c\Main\Wc\Entities\Category;
use Wc1c\Main\Wc\Products\Factory;
use Wc1c\Main\Wc\Products\SimpleProduct;
use Wc1c\Main\Wc\Products\VariableProduct;
use Wc1c\Main\Wc\Products\VariationVariableProduct;
use Wc1c\Main\Wc\Storage;

/**
 * Core
 *
 * @package Wc1c\Main\Schemas\Productscml
 */
class Core extends SchemaAbstract
{
	/**
	 * @var string Текущий каталог в файловой системе
	 */
	protected $upload_directory;

	/**
	 * @var Admin
	 */
	public $admin;

	/**
	 * @var Receiver
	 */
	public $receiver;

	/**
	 * Core constructor.
	 */
	public function __construct()
	{
		$this->setId('productscml');
		$this->setVersion('0.4.0');

		$this->setName(__('Products data exchange via CommerceML', 'wc1c-main'));
		$this->setDescription(__('Creation and updating of products (goods) in WooCommerce according to data from 1C using the CommerceML protocol of various versions.', 'wc1c-main'));
	}

	/**
	 * @param $admin
	 *
	 * @return void
	 */
	protected function setAdmin($admin)
	{
		$this->admin = $admin;
	}

	/**
	 * @param $receiver
	 *
	 * @return void
	 */
	protected function setReceiver($receiver)
	{
		$this->receiver = $receiver;
	}

	/**
	 * Initialize
	 *
	 * @return bool
	 */
	public function init(): bool
	{
		if($this->isInitialized())
		{
			return true;
		}

		$this->setOptions($this->configuration()->getOptions());
		$this->setUploadDirectory($this->configuration()->getUploadDirectory() . DIRECTORY_SEPARATOR . 'catalog');

		if(true === wc1c()->context()->isAdmin('plugin'))
		{
			$admin = Admin::instance();
			$admin->setCore($this);
			$admin->initConfigurationsFields();
			$this->setAdmin($admin);
		}

		if(true === wc1c()->context()->isReceiver())
		{
			$receiver = Receiver::instance();
			$receiver->setCore($this);
			$receiver->initHandler();
			$this->setReceiver($receiver);

			add_action('wc1c_schema_productscml_file_processing_read', [$this, 'processingTimer'], 5, 1);

			add_action('wc1c_schema_productscml_file_processing_read', [$this, 'processingClassifier'], 10, 1);
			add_action('wc1c_schema_productscml_file_processing_read', [$this, 'processingCatalog'], 20, 1);
			add_action('wc1c_schema_productscml_file_processing_read', [$this, 'processingOffers'], 20, 1);

			add_action('wc1c_schema_productscml_processing_classifier_item', [$this, 'processingClassifierItem'], 10, 2);
			add_action('wc1c_schema_productscml_processing_classifier_item', [$this, 'processingClassifierGroups'], 10, 2);
			add_action('wc1c_schema_productscml_processing_classifier_item', [$this, 'processingClassifierProperties'], 10, 2);

			add_action('wc1c_schema_productscml_processing_products_item', [$this, 'processingProductsItem'], 10, 2);
			add_action('wc1c_schema_productscml_processing_offers_item', [$this, 'processingOffersItem'], 10, 2);

			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemCatalogVisibility'], 10, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemReviews'], 10, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemSoldIndividually'], 10, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemFeatured'], 10, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemStatus'], 10, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemStockStatus'], 10, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemSku'], 10, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemName'], 10, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemDescriptions'], 10, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemDescriptionsFull'], 10, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemCategories'], 15, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemAttributes'], 15, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemDimensions'], 15, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemStatusTrash'], 100, 4);

			add_filter('wc1c_schema_productscml_processing_products_item_after_save', [$this, 'assignProductsItemImages'], 10, 4);

			add_filter('wc1c_schema_productscml_processing_offers_item_before_save', [$this, 'assignOffersItemAttributes'], 10, 3);
			add_filter('wc1c_schema_productscml_processing_offers_item_before_save', [$this, 'assignOffersItemPrices'], 10, 3);
			add_filter('wc1c_schema_productscml_processing_offers_item_before_save', [$this, 'assignOffersItemInventories'], 10, 3);
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function getUploadDirectory(): string
	{
		return $this->upload_directory;
	}

	/**
	 * @param mixed $upload_directory
	 */
	public function setUploadDirectory($upload_directory)
	{
		$this->upload_directory = $upload_directory;
	}

	/**
	 * CommerceML file processing
	 *
	 * @param string $file_path
	 *
	 * @return boolean true - success, false - error
	 */
	public function fileProcessing(string $file_path): bool
	{
		try
		{
			$decoder = new Decoder();
		}
		catch(Exception $exception)
		{
			$this->log()->error(__('The file cannot be processed. DecoderCML threw an exception.', 'wc1c-main'), ['exception' => $exception]);
			return false;
		}

		if(has_filter('wc1c_schema_productscml_file_processing_decoder'))
		{
			$decoder = apply_filters('wc1c_schema_productscml_file_processing_decoder', $decoder, $this);
			$this->log()->info(__('DecoderCML has been overridden by external algorithms.', 'wc1c-main'));
		}

		try
		{
			$reader = new Reader($file_path, $decoder);
		}
		catch(Exception $exception)
		{
			$this->log()->error(__('The file cannot be processed. ReaderCML threw an exception.', 'wc1c-main'), ['exception' => $exception]);
			return false;
		}

		$this->log()->debug(__('Filetype:', 'wc1c-main') . ' ' . $reader->getFiletype(), ['filetype' => $reader->getFiletype()]);

		if(has_filter('wc1c_schema_productscml_file_processing_reader'))
		{
			$reader = apply_filters('wc1c_schema_productscml_file_processing_reader', $reader, $this);
			$this->log()->info(__('ReaderCML has been overridden by external algorithms.', 'wc1c-main'));
		}

		while($reader->read())
		{
			try
			{
				do_action('wc1c_schema_productscml_file_processing_read', $reader, $this);
			}
			catch(Exception $e)
			{
				$this->log()->error(__('Import file processing not completed. ReaderCML threw an exception.', 'wc1c-main'), ['exception' => $e]);
				break;
			}
		}

		return $reader->ready;
	}

	/**
	 * Принудительное прерывание обработки при израсходовании доступного времени
	 *
	 * @param Reader $reader
	 *
	 * @return void
	 * @throws Exception
	 */
	public function processingTimer(Reader $reader)
	{
		if(wc1c()->timer()->getMaximum() !== 0 && !wc1c()->timer()->isRemainingBiggerThan(5))
		{
			throw new Exception(__('There was not enough time to load all the data.', 'wc1c-main'));
		}
	}

	/**
	 * Обработка данных классификатора
	 *
	 * @param Reader $reader
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function processingClassifier(Reader $reader)
	{
		if($reader->filetype !== 'import' && $reader->filetype !== 'offers')
		{
			return;
		}

		if($reader->nodeName === 'Классификатор' && $reader->xml_reader->nodeType === XMLReader::ELEMENT)
		{
			/**
			 * Декодируем данные классификатора из XML в объект
			 */
			$classifier = $reader->decoder()->process('classifier', $reader->xml_reader->readOuterXml());

			/**
			 * Внешняя обработка классификатора
			 *
			 * @param ClassifierDataContract $classifier
			 * @param SchemaAbstract $this
			 */
			if(has_filter('wc1c_schema_productscml_processing_classifier'))
			{
				$classifier = apply_filters('wc1c_schema_productscml_processing_classifier', $classifier, $this);
			}

			if(!$classifier instanceof ClassifierDataContract)
			{
				$this->log()->debug(__('Classifier !instanceof ClassifierDataContract. Skip processing.', 'wc1c-main'), ['data' => $classifier]);
				return;
			}

			$reader->classifier = $classifier;

			try
			{
				do_action('wc1c_schema_productscml_processing_classifier_item', $classifier, $reader, $this);
			}
			catch(Exception $e)
			{
				$this->log()->warning(__('An exception was thrown while saving the classifier.', 'wc1c-main'), ['exception' => $e]);
			}

			$reader->next();
		}
	}

	/**
	 * Processing groups from classifier
	 *
	 * @param ClassifierDataContract $classifier
	 * @param Reader $reader
	 *
	 * @return void
	 * @throws Exception
	 */
	public function processingClassifierGroups(ClassifierDataContract $classifier, Reader $reader)
	{
		if($reader->getFiletype() !== 'import')
		{
			return;
		}

		$classifier_groups = $classifier->getGroups();

		if(empty($classifier_groups))
		{
			$this->log()->info(__('Classifier groups is empty.', 'wc1c-main'));
			return;
		}

		$create_categories = $this->getOptions('categories_classifier_groups_create', 'no');
		$update_categories = $this->getOptions('categories_classifier_groups_update', 'no');
		$merge_categories = $this->getOptions('categories_merge', 'no');

		if
		(
			'yes' === $merge_categories
			||
			('yes' === $this->getOptions('categories_create', 'no') && 'yes' === $create_categories)
			||
			('yes' === $this->getOptions('categories_update', 'no') && 'yes' === $update_categories)
		)
		{
			$update_categories_only_configuration = $this->getOptions('categories_update_only_configuration', 'no');

			$assign_description = $this->getOptions('categories_classifier_groups_create_assign_description', 'no');
			$assign_parent = $this->getOptions('categories_classifier_groups_create_assign_parent', 'yes');

			$update_parent = $this->getOptions('categories_classifier_groups_update_parent', 'yes');
			$update_description = $this->getOptions('categories_classifier_groups_update_description', 'no');
			$update_name = $this->getOptions('categories_classifier_groups_update_name', 'no');

			/** @var CategoriesStorageContract $categories_storage */
			$categories_storage = Storage::load('category');

			foreach($classifier_groups as $group_id => $group)
			{
				$category = false;

				$this->log()->debug(__('Classifier group processing.', 'wc1c-main'), ['group_id' => $group_id, 'group' => $group]);

				/**
				 * Поиск существующей категории по внешним алгоритмам
				 *
				 * @param SchemaAbstract $schema Текущая схема
				 * @param array $property Данные категории в CML
				 * @param Reader $reader Текущий итератор
				 *
				 * @return int|false
				 */
				if(has_filter('wc1c_schema_productscml_processing_classifier_groups_category_search'))
				{
					$category = apply_filters('wc1c_schema_productscml_processing_classifier_groups_category_search', $this, $group, $reader);
				}

				/*
				 * Поиск категории по идентификатору из классификатора
				 */
				if(false === $category)
				{
					$category = $categories_storage->getByExternalId($group_id);
				}

				/**
				 * Найдено несколько категорий с одним и тем же идентификатором из классификатора
				 */
				if(!empty($category) && is_array($category))
				{
					$this->log()->warning(__('More than one category found by ID from 1C. Assigning the first available.', 'wc1c-main'), ['categories' => $category]);
					$category = $category[0];
				}

				/**
				 * Категория не найдена, но включено использование существующих
				 */
				if(!$category instanceof Category && 'no' !== $merge_categories)
				{
					$cats = [];
					$category_merge = false;

					$category = $categories_storage->getByName($group['name']);

					if(false === $category)
					{
						$this->log()->info(__('No category found for the specified name.', 'wc1c-main'));
					}
					else
					{
						if(is_array($category))
						{
							$cats = $category;
						}
						else
						{
							$cats[] = $category;
						}

						foreach($cats as $cat)
						{
							/*
							 * Первая попавшееся категория по имени
							 */
							if('yes' === $merge_categories)
							{
								$category_merge = true;
								$category = $cat;
								break;
							}

							/*
							 * С учетом родительской категории
							 */
							if('yes_parent' === $merge_categories)
							{
								/*
								 * Родитель отсутствует в 1С и в WooCommerce
								 */
								if(false === $cat->hasParent() && empty($group['parent_id']))
								{
									$category_merge = true;
									$category = $cat;
									break;
								}

								/*
								 * Родитель присутствует в 1С и в WooCommerce
								 */
								if(true === $cat->hasParent() && !empty($group['parent_id']))
								{
									$parent_category_check_classifier = $classifier_groups[$group['parent_id']];
									$parent_category = new Category($cat->getParentId());

									if($parent_category->getName() === $parent_category_check_classifier['name'])
									{
										$category_merge = true;
										$category = $cat;
										break;
									}
								}
							}
						}
					}

					/**
					 * Слияние разрешено
					 */
					if($category_merge)
					{
						// Назначение идентификатора категории
						$category->assignExternalId($group_id);

						// Назначение идентификатора родительской категории
						if(!empty($group['parent_id']))
						{
							$category->assignExternalParentId($group['parent_id']);
						}

						// Обновление отключено, либо доступно только при совпадении конфигураций
						if('yes' !== $update_categories || 'yes' === $update_categories_only_configuration)
						{
							$category->save();
						}
					}
				}

				/**
				 * Категория найдена и включено обновление данных
				 */
				if($category instanceof Category && 'yes' === $update_categories)
				{
					$this->log()->info(__('The category exists. Started updating the data of an existing category.', 'wc1c-main'));

					/**
					 * Пропуск созданных категорий не под текущей конфигурацией
					 */
					if('yes' === $update_categories_only_configuration && (int)$category->getConfigurationId() !== $this->configuration()->getId())
					{
						$this->log()->warning(__('Category update skipped. The category was created from a different configuration.', 'wc1c-main'));
						continue;
					}

					/**
					 * Обновление имени
					 */
					if('yes' === $update_name)
					{
						$category->setName($group['name']);
					}

					/**
					 * Обновление описания
					 */
					if('yes' === $update_description)
					{
						$category->setDescription($group['description']);
					}

					/**
					 * Обновление родительской категории
					 */
					if('yes' === $update_parent)
					{
						if(empty($group['parent_id']))
						{
							$category->setParentId(0);
						}
						else
						{
							$parent_category = $categories_storage->getByExternalId($group['parent_id']);

							/**
							 * Найдено несколько категорий с одним и тем же идентификатором из классификатора
							 */
							if(!empty($parent_category) && is_array($parent_category))
							{
								$this->log()->warning(__('More than one parent category found by ID from 1C. Assigning the first available.', 'wc1c-main'), ['categories' => $parent_category]);
								$parent_category = $parent_category[0];
							}

							if($parent_category instanceof Category && $category->getParentId() !== $parent_category->getId())
							{
								$category->setParentId($parent_category->getId());
								$category->assignExternalParentId($group['parent_id']);
							}
						}
					}

					$category->save();

					$this->log()->info(__('Update of existing category data completed successfully.', 'wc1c-main'));
					continue;
				}

				/**
				 * Категория не найдена и включено создание
				 */
				if('yes' === $create_categories)
				{
					$this->log()->info(__('The category does not exist. Category creation started.', 'wc1c-main'));

					$category = new Category();

					/**
					 * Назначение технических данных WC1C
					 */
					$category->setSchemaId($this->getId());
					$category->setConfigurationId($this->configuration()->getId());

					/**
					 * Привязка идентификатора из 1С к WooCommerce
					 */
					$category->assignExternalId($group_id);

					/**
					 * Назначение родительской категории
					 */
					if('yes' === $assign_parent && !empty($group['parent_id']))
					{
						$parent_category = $categories_storage->getByExternalId($group['parent_id']);

						/**
						 * Найдено несколько категорий с одним и тем же идентификатором из классификатора
						 */
						if(!empty($parent_category) && is_array($parent_category))
						{
							$this->log()->warning(__('More than one parent category found by ID from 1C. Assigning the first available.', 'wc1c-main'), ['categories' => $parent_category]);
							$parent_category = $parent_category[0];
						}

						if($parent_category instanceof Category)
						{
							$category->setParentId($parent_category->getId());
							$category->assignExternalParentId($group['parent_id']);
						}
					}

					/**
					 * Назначение имени категории
					 */
					$category->setName($group['name']);

					/**
					 * Назначение описания категории
					 */
					if('yes' === $assign_description)
					{
						$category->setDescription($group['description']);
					}

					$category->save();

					$this->log()->info(__('Category creation completed successfully.', 'wc1c-main'), ['category' => $category]);
					continue;
				}

				$this->log()->debug(__('No action was taken for the classifier group.', 'wc1c-main'));
			}

			return;
		}

		$this->log()->info(__('Creating, updating and using categories is disabled.', 'wc1c-main'));
	}

	/**
	 * Processing properties from classifier
	 *
	 * @param ClassifierDataContract $classifier
	 * @param Reader $reader
	 *
	 * @return void
	 * @throws Exception
	 */
	public function processingClassifierProperties(ClassifierDataContract $classifier, Reader $reader)
	{
		if($reader->getFiletype() !== 'import' && $reader->getFiletype() !== 'offers')
		{
			return;
		}

		$classifier_properties = $classifier->getProperties();

		if(empty($classifier_properties))
		{
			$this->log()->info(__('Classifier properties is empty.', 'wc1c-main'), ['filetype' => $reader->getFiletype()]);
			return;
		}

		$create_attributes = $this->getOptions('attributes_create_by_classifier_properties', 'no');
		$update_attributes_values = $this->getOptions('attributes_values_by_classifier_properties', 'no');

		if
		(
			('yes' === $this->getOptions('attributes_create', 'no') && 'yes' === $create_attributes)
			||
			('yes' === $this->getOptions('attributes_update', 'no') && 'yes' === $update_attributes_values)
		)
		{
			$this->log()->info(__('Creating attributes based on classifier properties.', 'wc1c-main'));

			/** @var AttributesStorageContract $attributes_storage */
			$attributes_storage = Storage::load('attribute');

			foreach($classifier_properties as $property_id => $property)
			{
				$attribute = false;

				$this->log()->debug(__('Classifier properties processing.', 'wc1c-main'), ['property_id' => $property_id, 'property' => $property]);

				/**
				 * Поиск существующего атрибута по внешним алгоритмам
				 *
				 * @param SchemaAbstract $schema Текущая схема
				 * @param array $property Данные свойства в CML
				 * @param Reader $reader Текущий итератор
				 *
				 * @return int|false
				 */
				if(has_filter('wc1c_schema_productscml_processing_classifier_properties_attribute_search'))
				{
					$this->log()->info(__('Attribute search by external algorithms for the classifier property.', 'wc1c-main'));
					$attribute = apply_filters('wc1c_schema_productscml_processing_classifier_properties_attribute_search', $this, $property, $reader);

					if($attribute instanceof AttributeContract)
					{
						$this->log()->info(__('An existing attribute was found when searching by external algorithms.', 'wc1c-main'), ['property_name' => $property['name'], 'attribute' => $attribute]);
					}
				}

				/*
				 * Поиск атрибута по наименованию
				 */
				if(!$attribute instanceof AttributeContract)
				{
					$this->log()->info(__('Search for an attribute by name for a classifier property.', 'wc1c-main'), ['property_name' => $property['name']]);
					$attribute = $attributes_storage->getByLabel($property['name']);

					if($attribute instanceof AttributeContract)
					{
						$this->log()->info(__('An existing attribute was found when searching by name.', 'wc1c-main'), ['property_name' => $property['name'], 'attribute' => $attribute]);
					}
				}

				/*
				 * Не найден - создаем
				 */
				if(!$attribute instanceof AttributeContract)
				{
					if('yes' === $create_attributes && 'yes' === $this->getOptions('attributes_create', 'no'))
					{
						$this->log()->info(__('The attribute was not found. Creating.', 'wc1c-main'));

						$attribute = new Attribute();
						$attribute->setLabel($property['name']);

						$attribute->save();
					}
					else
					{
						$this->log()->info(__('The attribute was not found. Creating disabled.', 'wc1c-main'));
					}
				}

				/*
				 * Добавляем варианты значений
				 */
				if($attribute instanceof AttributeContract && isset($property['values_variants']) && !empty($property['values_variants']))
				{
					$this->log()->info(__('Values for the attribute were found in the classifier properties. Processing.', 'wc1c-main'));

					if('yes' === $update_attributes_values && 'yes' === $this->getOptions('attributes_update', 'no'))
					{
						foreach($property['values_variants'] as $values_variant_id => $values_variant)
						{
							// todo: search before add
							$this->log()->info(__('Adding a value for an attribute.', 'wc1c-main'), ['attribute_name' => $attribute->getName(), 'value' => $values_variant]);

							if(!$attribute->assignValue($values_variant))
							{
								$this->log()->info(__('Failed to add value for attribute.', 'wc1c-main'), ['attribute_name' => $attribute->getName(), 'value' => $values_variant]);
							}
						}
					}
					else
					{
						$this->log()->info(__('Adding values for attributes based on classifier properties is disabled.', 'wc1c-main'));
					}
				}
			}

			$this->log()->info(__('The creation and updates of attributes based on the classifier properties has been successfully completed.', 'wc1c-main'));
			return;
		}

		$this->log()->info(__('Creating, updating and using attributes is disabled.', 'wc1c-main'));
	}

	/**
	 * Save classifier
	 *
	 * @param ClassifierDataContract $classifier
	 * @param Reader $reader
	 *
	 * @return void
	 */
	public function processingClassifierItem(ClassifierDataContract $classifier, Reader $reader)
	{
		if($reader->getFiletype() !== 'import' && $reader->getFiletype() !== 'offers')
		{
			return;
		}

		$this->configuration()->updateMetaData('classifier:' . $reader->getFiletype() . ':' . $classifier->getId(), maybe_serialize($classifier));
		$this->configuration()->saveMetaData();

		$classifier_properties = $classifier->getProperties();
		if(!empty($classifier_properties))
		{
			$this->configuration()->updateMetaData('classifier-properties:' . $reader->getFiletype() . ':' . $classifier->getId(), maybe_serialize($classifier_properties));
			$this->configuration()->saveMetaData();
		}

		$this->configuration()->readMetaData();
	}

	/**
	 * Обработка каталога товаров
	 *
	 * @param Reader $reader
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function processingCatalog(Reader $reader)
	{
		if($reader->getFiletype() !== 'import')
		{
			return;
		}

		if(is_null($reader->catalog))
		{
			$reader->catalog = new Catalog();
		}

		if($reader->nodeName === 'Каталог' && $reader->xml_reader->nodeType === XMLReader::ELEMENT)
		{
			$only_changes = $reader->xml_reader->getAttribute('СодержитТолькоИзменения') ?: true;
			if($only_changes === 'false')
			{
				$only_changes = false;
			}
			$reader->catalog->setOnlyChanges($only_changes);
		}

		if($reader->parentNodeName === 'Каталог' && $reader->xml_reader->nodeType === XMLReader::ELEMENT)
		{
			switch($reader->nodeName)
			{
				case 'Ид':
					$reader->catalog->setId($reader->xml_reader->readString());
					break;
				case 'ИдКлассификатора':
					$reader->catalog->setClassifierId($reader->xml_reader->readString());
					break;
				case 'Наименование':
					$reader->catalog->setName($reader->xml_reader->readString());
					break;
				case 'Владелец':
					$owner = $reader->decoder()->process('counterparty', $reader->xml_reader->readOuterXml());
					$reader->catalog->setOwner($owner);
					break;
				case 'Описание':
					$reader->catalog->setDescription($reader->xml_reader->readString());
					break;
			}
		}

		/*
		 * Пропуск создания и обновления продуктов
		 */
		if
		(
			$reader->nodeName === 'Товары' && $reader->xml_reader->nodeType === XMLReader::ELEMENT &&
			'yes' !== $this->getOptions('products_update', 'no') && 'yes' !== $this->getOptions('products_create', 'no')
		)
		{
			$this->log()->info(__('Products creation and updating is disabled. The processing of goods was skipped.', 'wc1c-main'));
			$reader->next();
		}

		if($reader->parentNodeName === 'Товары' && $reader->nodeName === 'Товар' && $reader->xml_reader->nodeType === XMLReader::ELEMENT)
		{
			/**
			 * Декодирование данных продукта из XML в объект реализующий ProductDataContract
			 */
			$product = $reader->decoder->process('product', $reader->xml_reader->readOuterXml());

			/**
			 * Внешняя фильтрация перед непосредственной обработкой
			 *
			 * @param ProductDataContract $product
			 * @param Reader $reader
			 * @param SchemaAbstract $this
			 */
			if(has_filter('wc1c_schema_productscml_processing_products'))
			{
				$product = apply_filters('wc1c_schema_productscml_processing_products', $product, $reader, $this);
			}

			if(!$product instanceof ProductDataContract)
			{
				$this->log()->debug(__('Product !instanceof ProductDataContract. Skip processing.', 'wc1c-main'), ['data' => $product]);
				return;
			}

			/*
			 * Пропуск продуктов с характеристиками
			 */
			if(true === $product->hasCharacteristicId() && 'yes' !== $this->getOptions('products_with_characteristics', 'no'))
			{
				$this->log()->info(__('The use of products with characteristics is disabled. Processing skipped.', 'wc1c-main'));
				return;
			}

			try
			{
				do_action('wc1c_schema_productscml_processing_products_item', $product, $reader, $this);
			}
			catch(Exception $e)
			{
				$this->log()->warning(__('An exception was thrown while saving the product.', 'wc1c-main'), ['exception' => $e]);
			}

			$reader->next();
		}
	}

	/**
	 * Назначение данных продукта исходя из режима: наименование
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemName(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if($internal_product->isType('variation'))
		{
			return $internal_product;
		}

		$source = $this->getOptions('products_names_by_cml', 'name');

		if('no' === $source)
		{
			return $internal_product;
		}

		if('update' === $mode && 'yes' !== $this->getOptions('products_update_name', 'no'))
		{
			return $internal_product;
		}

		$name = '';

		switch($source)
		{
			case 'full_name':
				$requisite = 'Полное наименование';
				if($external_product->hasRequisites($requisite))
				{
					$requisite_data = $external_product->getRequisites($requisite);
					if(!empty($requisite_data['value']))
					{
						$name = $requisite_data['value'];
					}
				}
				break;
			case 'yes_requisites':
				$requisite = $this->getOptions('products_names_from_requisites_name', '');
				if($external_product->hasRequisites($requisite))
				{
					$requisite_data = $external_product->getRequisites($requisite);
					if(!empty($requisite_data['value']))
					{
						$name = $requisite_data['value'];
					}
				}
				break;
			default:
				$name = $external_product->getName();
		}

		$name = wp_strip_all_tags($name);

		$internal_product->set_name($name);

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: артикул
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemSku(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		try
		{
			$internal_product->setSku($external_product->getSku());
		}
		catch(Exception $e)
		{
			$this->log()->notice(__('Failed to set SKU for product.', 'wc1c-main'), ['exception' => $e, 'sku' => $external_product->getSku()]);
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: видимость в каталоге
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemCatalogVisibility(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if($internal_product->isType('variation'))
		{
			return $internal_product;
		}

		if($mode === 'create')
		{
			$internal_product->set_catalog_visibility($this->getOptions('products_create_set_catalog_visibility', 'visible'));
			return $internal_product;
		}

		$internal_product->set_catalog_visibility($this->getOptions('products_update_set_catalog_visibility', 'visible'));

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: отзывы
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemReviews(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if($internal_product->isType('variation'))
		{
			return $internal_product;
		}

		if($mode === 'create')
		{
			$internal_product->set_reviews_allowed(false);
			if('yes' === $this->getOptions('products_create_set_reviews_allowed', 'no'))
			{
				$internal_product->set_reviews_allowed(true);
			}

			return $internal_product;
		}

		if('yes' === $this->getOptions('products_update_set_reviews_allowed', 'no'))
		{
			$internal_product->set_reviews_allowed(true);
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: индивидуальная продажа
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemSoldIndividually(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if($mode === 'create')
		{
			if('yes' === $this->getOptions('products_create_set_sold_individually', 'no'))
			{
				$internal_product->set_sold_individually(true);
			}

			return $internal_product;
		}

		if('yes' === $this->getOptions('products_update_set_sold_individually', 'no'))
		{
			$internal_product->set_sold_individually(true);
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: рекомендуемый
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemFeatured(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if($internal_product->isType('variation'))
		{
			return $internal_product;
		}

		if($mode === 'create')
		{
			if('yes' === $this->getOptions('products_create_set_featured', 'no'))
			{
				$internal_product->set_featured(true);
			}

			return $internal_product;
		}

		if('yes' === $this->getOptions('products_update_set_featured', 'no'))
		{
			$internal_product->set_featured(true);
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: статус
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemStatus(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if($mode === 'create')
		{
			$create_status = $this->getOptions('products_create_status', 'draft');

			$internal_product->set_status($create_status);

			return $internal_product;
		}

		$update_status = $this->getOptions('products_update_status', '');

		if(!empty($update_status))
		{
			$internal_product->set_status($update_status);
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: статус корзины
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemStatusTrash(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if($internal_product->isType('variation'))
		{
			return $internal_product;
		}

		$raw = $external_product->getData(); // todo: вынести в метод

		if($mode === 'create'&& isset($raw['delete_mark']) && $raw['delete_mark'] === 'yes' && 'yes' === $this->getOptions('products_create_delete_mark_trash', 'no'))
		{
			$internal_product->set_status('trash');
		}

		if($mode === 'update'&& 'yes' === $this->getOptions('products_update_delete_mark_trash', 'no'))
		{
			if(isset($raw['delete_mark']) && $raw['delete_mark'] === 'yes')
			{
				$internal_product->set_status('trash');
			}
			else
			{
				$update_status = $this->getOptions('products_update_status', '');

				if(!empty($update_status))
				{
					$internal_product->set_status($update_status);
				}
			}
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: статус остатка
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemStockStatus(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if($mode === 'create')
		{
			$internal_product->set_stock_status($this->getOptions('products_create_stock_status', 'outofstock'));

			return $internal_product;
		}

		$update_status = $this->getOptions('products_update_stock_status', '');

		if(!empty($update_status))
		{
			$internal_product->set_stock_status($update_status);
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: описания
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemDescriptions(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if('create' === $mode && 'yes' !== $this->getOptions('products_create_adding_description', 'yes'))
		{
			return $internal_product;
		}

		if('update' === $mode && 'yes' !== $this->getOptions('products_update_description', 'no'))
		{
			return $internal_product;
		}

		$short = $this->getOptions('products_descriptions_short_by_cml', 'no');

		if('no' !== $short)
		{
			$short_description = '';
			switch($short)
			{
				case 'yes_html':
					$requisite = 'ОписаниеВФорматеHTML';
					if($external_product->hasRequisites($requisite))
					{
						$requisite_data = $external_product->getRequisites($requisite);
						if(!empty($requisite_data['value']))
						{
							$short_description = html_entity_decode($requisite_data['value']);
						}
					}
					break;
				case 'yes_requisites':
					$requisite = $this->getOptions('products_descriptions_short_from_requisites_name', '');
					if($external_product->hasRequisites($requisite))
					{
						$requisite_data = $external_product->getRequisites($requisite);
						if(!empty($requisite_data['value']))
						{
							$short_description = html_entity_decode($requisite_data['value']);
						}
					}
					break;
				default:
					$short_description = $external_product->getDescription();
			}

			$internal_product->set_short_description($short_description);
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: описания
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemDescriptionsFull(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if('create' === $mode && 'yes' !== $this->getOptions('products_create_adding_description_full', 'no'))
		{
			return $internal_product;
		}

		if('update' === $mode && 'yes' !== $this->getOptions('products_update_description_full', 'no'))
		{
			return $internal_product;
		}

		$full = $this->getOptions('products_descriptions_by_cml', 'no');

		if('no' !== $full)
		{
			$full_description = '';
			switch($full)
			{
				case 'yes_html':
					$requisite = 'ОписаниеВФорматеHTML';
					if($external_product->hasRequisites($requisite))
					{
						$requisite_data = $external_product->getRequisites($requisite);
						if(!empty($requisite_data['value']))
						{
							$full_description = html_entity_decode($requisite_data['value']);
						}
					}
					break;
				case 'yes_requisites':
					$requisite = $this->getOptions('products_descriptions_from_requisites_name', '');
					if($external_product->hasRequisites($requisite))
					{
						$requisite_data = $external_product->getRequisites($requisite);
						if(!empty($requisite_data['value']))
						{
							$full_description = html_entity_decode($requisite_data['value']);
						}
					}
					break;
				default:
					$full_description = $external_product->getDescription();
			}

			$internal_product->set_description($full_description);
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: категории
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 * @throws Exception
	 */
	public function assignProductsItemCategories(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if('create' === $mode && 'yes' !== $this->getOptions('products_create_adding_category', 'yes'))
		{
			return $internal_product;
		}

		if('update' === $mode && 'yes' !== $this->getOptions('products_update_categories', 'no'))
		{
			return $internal_product;
		}

		if($internal_product->isType('variation'))
		{
			return $internal_product;
		}

		if('create' === $mode && false === $external_product->hasClassifierGroups())
		{
			return $internal_product;
		}

		/** @var CategoriesStorageContract $categories_storage */
		$categories_storage = Storage::load('category');

		$cats = [];
		foreach($external_product->getClassifierGroups() as $classifier_group)
		{
			$cat = $categories_storage->getByExternalId($classifier_group);

			if($cat instanceof Category)
			{
				$cats[] = $cat->getId();
			}
		}

		$internal_product->set_category_ids($cats);

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: изображения
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 * @throws Exception
	 */
	public function assignProductsItemImages(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if('create' === $mode && false === $external_product->hasImages())
		{
			return $internal_product;
		}

		if($internal_product->isType('variation')) // todo: назначение одного изображения для вариации
		{
			return $internal_product;
		}

		if('create' === $mode && 'yes' !== $this->getOptions('products_create_adding_images', 'no'))
		{
			return $internal_product;
		}

		if('update' === $mode && 'yes' !== $this->getOptions('products_update_images', 'no'))
		{
			return $internal_product;
		}

		if('yes' !== $this->getOptions('products_images_by_cml', 'no'))
		{
			return $internal_product;
		}

		$max_images = $this->getOptions('products_images_by_cml_max', 10);

		/** @var ImagesStorageContract $images_storage */
		$images_storage = Storage::load('image');

		$images = $external_product->getImages();
		$gallery_image_ids = [];

		if(is_array($images))
		{
			foreach($images as $index => $image)
			{
				if($index >= $max_images)
				{
					$this->log()->notice(__('The maximum possible number of images has been processed. The rest of the images are skip.', 'wc1c-main'));
					break;
				}

				$file = explode('.', basename($image));

				$image_current = $images_storage->getByExternalName(reset($file));

				if(is_array($image_current))
				{
					$image_current = reset($image_current);
				}

				$attach_id = $image_current->getId();

				if(0 === $attach_id)
				{
					$this->log()->notice(__('The image assignment for the product is missing. It is not found in the media library.', 'wc1c-main'), ['image' => $image]);
					continue;
				}

				$image_current->setProductId($internal_product->getId());
				$image_current->save();

				if($index === 0)
				{
					$internal_product->set_image_id($attach_id);
					continue;
				}

				$gallery_image_ids[] = $attach_id;
			}
		}

		$internal_product->set_gallery_image_ids($gallery_image_ids);

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: габариты
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignProductsItemDimensions(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if('yes' !== $this->getOptions('products_dimensions_by_requisites', 'no'))
		{
			return $internal_product;
		}

		/**
		 * Вес
		 */
		$weight = '';
		$weight_name = trim($this->getOptions('products_dimensions_by_requisites_weight_from_name', 'Вес'));

		if($weight_name !== '' && $external_product->hasRequisites($weight_name))
		{
			$requisite_data = $external_product->getRequisites($weight_name);
			if(!empty($requisite_data['value']))
			{
				$weight = $requisite_data['value'];
			}
		}

		if(has_filter('wc1c_products_dimensions_by_requisites_weight'))
		{
			$weight = apply_filters('wc1c_products_dimensions_by_requisites_weight', $weight, $internal_product, $external_product, $mode, $reader, $this);
		}

		$internal_product->set_weight($weight);

		/**
		 * Длина
		 */
		$length = '';
		$length_name = trim($this->getOptions('products_dimensions_by_requisites_length_from_name', 'Длина'));

		if($length_name !== '' && $external_product->hasRequisites($length_name))
		{
			$requisite_data = $external_product->getRequisites($length_name);
			if(!empty($requisite_data['value']))
			{
				$length = $requisite_data['value'];
			}
		}

		if(has_filter('wc1c_products_dimensions_by_requisites_length'))
		{
			$length = apply_filters('wc1c_products_dimensions_by_requisites_length', $length, $internal_product, $external_product, $mode, $reader, $this);
		}

		$internal_product->set_length($length);

		/**
		 * Ширина
		 */
		$width = '';
		$width_name = trim($this->getOptions('products_dimensions_by_requisites_width_from_name', 'Ширина'));

		if($width_name !== '' && $external_product->hasRequisites($width_name))
		{
			$requisite_data = $external_product->getRequisites($width_name);
			if(!empty($requisite_data['value']))
			{
				$width = $requisite_data['value'];
			}
		}

		if(has_filter('wc1c_products_dimensions_by_requisites_width'))
		{
			$width = apply_filters('wc1c_products_dimensions_by_requisites_width', $width, $internal_product, $external_product, $mode, $reader, $this);
		}

		$internal_product->set_width($width);

		/**
		 * Высота
		 */
		$height = '';
		$height_name = trim($this->getOptions('products_dimensions_by_requisites_height_from_name', 'Высота'));

		if($height_name !== '' && $external_product->hasRequisites($height_name))
		{
			$requisite_data = $external_product->getRequisites($height_name);
			if(!empty($requisite_data['value']))
			{
				$height = $requisite_data['value'];
			}
		}

		if(has_filter('wc1c_products_dimensions_by_requisites_height'))
		{
			$height = apply_filters('wc1c_products_dimensions_by_requisites_height', $height, $internal_product, $external_product, $mode, $reader, $this);
		}

		$internal_product->set_height($height);

		return $internal_product;
	}

	/**
	 * Set product attributes.
	 *
	 * @param ProductContract $product Product instance.
	 * @param array $raw_attributes Attributes data.
	 *
	 * @return ProductContract
	 * @throws Exception If data cannot be set.
	 */
	protected function setProductAttributes(&$product, array $raw_attributes): ProductContract
	{
		$this->log()->debug(__('Assigning attributes for product.', 'wc1c-main'), ['product_id' => $product->getId(), 'product_type' => $product->get_type(), 'raw_attributes' => $raw_attributes]);

		if(!empty($raw_attributes))
		{
			/** @var AttributesStorageContract $attributes_storage */
			$attributes_storage = Storage::load('attribute');

			$default_attributes = [];
			$attributes = [];

			$raw_attributes_counter = 0;
			$existing_attributes = $product->get_attributes();

			foreach($raw_attributes as $attribute)
			{
				$attribute_id = 0;
				$attribute_exist = $attributes_storage->getByName($attribute['name']);

				// Get ID if is a global attribute.
				if(!empty($attribute['taxonomy']))
				{
					$attribute_id = $attribute_exist ? $attribute_exist->getId() : 0;
				}

				// Set attribute visibility.
				$is_visible = $attribute['visible'] ?? 1;

				// Set attribute position.
				$position = $attribute['position'] ?? $raw_attributes_counter;

				// Get name.
				$attribute_name = $attribute_id ? $attribute_exist->getTaxonomyName() : $attribute['name'];

				// Set if is a variation attribute based on existing attributes if possible so updates via CSV do not change this.
				$is_variation = $attribute['variation'] ?? 0;

				if($existing_attributes)
				{
					foreach($existing_attributes as $existing_attribute)
					{
						if($existing_attribute->get_name() === $attribute_name)
						{
							$is_variation = $existing_attribute->get_variation();
							break;
						}
					}
				}

				if($attribute_id)
				{
					if(isset($attribute['value']))
					{
						$options = array_map('wc_sanitize_term_text_based', $attribute['value']);
						$options = array_filter($options, 'strlen');
					}
					else
					{
						$options = [];
					}

					// Check for default attributes and set "is_variation".
					if(!empty($attribute['default']) && in_array($attribute['default'], $options, true))
					{
						$default_term = get_term_by('name', $attribute['default'], $attribute_name);

						if($default_term && !is_wp_error($default_term))
						{
							$default = $default_term->slug;
						}
						else
						{
							$default = sanitize_title($attribute['default']);
						}

						$default_attributes[$attribute_name] = $default;
						$is_variation = 1;
					}

					if(!empty($options))
					{
						$attribute_object = new AttributeProduct();

						$attribute_object->set_id($attribute_id);
						$attribute_object->set_name($attribute_name);
						$attribute_object->set_options($options);
						$attribute_object->set_position($position);
						$attribute_object->set_visible($is_visible);
						$attribute_object->set_variation($is_variation);

						$attributes[] = $attribute_object;
					}
				}
				elseif(isset($attribute['value']))
				{
					// Check for default attributes and set "is_variation".
					if(!empty($attribute['default']) && in_array($attribute['default'], $attribute['value'], true))
					{
						$default_attributes[sanitize_title($attribute['name'])] = $attribute['default'];
						$is_variation = 1;
					}

					$attribute_object = new AttributeProduct();

					$attribute_object->set_name($attribute['name']);
					$attribute_object->set_options($attribute['value']);
					$attribute_object->set_position($position);
					$attribute_object->set_visible($is_visible);
					$attribute_object->set_variation($is_variation);

					$attributes[] = $attribute_object;
				}

				$raw_attributes_counter++;
			}

			$product->set_attributes($attributes);
			$this->log()->debug(__('Adding attributes to the product is successfully.', 'wc1c-main'), ['attributes' => $attributes]);

			// Set variable default attributes.
			if($product->isType('variable'))
			{
				$product->set_default_attributes($default_attributes);
				$this->log()->debug(__('Adding default attributes to the variable product is successfully.', 'wc1c-main'), ['default_attributes' => $default_attributes]);
			}
		}

		return $product;
	}

	/**
	 * Set variation attributes.
	 *
	 * @param ProductContract $variation Product instance.
	 * @param array $raw_attributes Attributes data.
	 *
	 * @return ProductContract
	 * @throws Exception If data cannot be set.
	 */
	protected function setVariationAttributes(ProductContract $variation, array $raw_attributes): ProductContract
	{
		$this->log()->debug(__('Assigning attributes for variation.', 'wc1c-main'), ['variation_id' => $variation->getId(), 'raw_attributes' => $raw_attributes]);

		/** @var AttributesStorageContract $attributes_storage */
		$attributes_storage = Storage::load('attribute');

		$parent = (new Factory)->getProduct($variation->get_parent_id()); // todo: cache

		// Stop if parent does not exist.
		if(!$parent)
		{
			$this->log()->warning(__('The parent product was not found. Skipped.', 'wc1c-main'), ['parent_id' => $variation->get_parent_id()]);
			return $variation;
		}

		if($parent->isType('variation'))
		{
			$this->log()->warning(__('The parent product is variation. Skipped.', 'wc1c-main'), ['parent_id' => $variation->get_parent_id()]);
			return $variation;
		}

		if(!empty($raw_attributes))
		{
			$attributes = [];
			$parent_attributes = $this->getVariationParentAttributes($raw_attributes, $parent);

			foreach($raw_attributes as $attribute)
			{
				$attribute_id = 0;
				$attribute_exist = $attributes_storage->getByName($attribute['name']);

				// Get ID if is a global attribute.
				if(!empty($attribute['taxonomy']))
				{
					$attribute_id = $attribute_exist ? $attribute_exist->getId() : 0;
				}

				$attribute_name = $attribute_id ? $attribute_exist->getTaxonomyName() : sanitize_title($attribute['name']);

				if(!isset($parent_attributes[$attribute_name]) || !$parent_attributes[$attribute_name]->get_variation())
				{
					continue;
				}

				$attribute_key = sanitize_title($parent_attributes[$attribute_name]->get_name());
				$attribute_value = isset($attribute['value']) ? current($attribute['value']) : '';

				if($parent_attributes[$attribute_name]->is_taxonomy())
				{
					// If dealing with a taxonomy, we need to get the slug from the name posted to the API.
					$term = get_term_by('name', $attribute_value, $attribute_name);

					if($term && !is_wp_error($term))
					{
						$attribute_value = $term->slug;
					}
					else
					{
						$attribute_value = sanitize_title($attribute_value);
					}
				}

				$attributes[$attribute_key] = $attribute_value;
			}

			$variation->set_attributes($attributes);
			$this->log()->debug(__('Adding attributes to the variation is successfully.', 'wc1c-main'), ['attributes' => $attributes]);
		}

		return $variation;
	}

	/**
	 * Get variation parent attributes and set "is_variation".
	 *
	 * @param array $attributes Attributes list.
	 * @param ProductContract $parent Parent product data.
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function getVariationParentAttributes(array $attributes, ProductContract $parent): array
	{
		/** @var AttributesStorageContract $attributes_storage */
		$attributes_storage = Storage::load('attribute');

		$parent_attributes = $parent->get_attributes();
		$require_save = false;

		foreach($attributes as $attribute)
		{
			$attribute_id = 0;
			$attribute_exist = $attributes_storage->getByName($attribute['name']);

			// Get ID if is a global attribute.
			if(!empty($attribute['taxonomy']))
			{
				$attribute_id = $attribute_exist ? $attribute_exist->getId() : 0;
			}

			$attribute_name = $attribute_id ? $attribute_exist->getTaxonomyName() : sanitize_title($attribute['name']);

			// Check if attribute handle variations.
			if(isset($parent_attributes[$attribute_name]) && !$parent_attributes[$attribute_name]->get_variation())
			{
				// Re-create the attribute to CRUD save and generate again.
				$parent_attributes[$attribute_name] = clone $parent_attributes[$attribute_name];
				$parent_attributes[$attribute_name]->set_variation(1);

				$require_save = true;
			}
		}

		// Save variation attributes.
		if($require_save)
		{
			$parent->set_attributes(array_values($parent_attributes));
			$parent->save();
		}

		return $parent_attributes;
	}

	/**
	 * Назначение данных продукта исходя из режима: атрибуты
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 * @throws Exception
	 */
	public function assignProductsItemAttributes(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if('create' === $mode && 'yes' !== $this->getOptions('products_create_adding_attributes', 'yes'))
		{
			return $internal_product;
		}

		if('update' === $mode && 'yes' !== $this->getOptions('products_update_attributes', 'no'))
		{
			return $internal_product;
		}

		$this->log()->debug(__('Assigning attributes to a product based on the properties of the product catalog.', 'wc1c-main'), ['mode' => $mode, 'filetype' => $reader->getFiletype(), 'internal_product_id' => $internal_product->getId(), 'external_product_id' => $external_product->getId()]);

		if($reader->getFiletype() !== 'import')
		{
			return $internal_product;
		}

		if($internal_product->isType('variable') && empty($external_product->getCharacteristicId()))
		{
			$this->log()->info(__('Zeroing the characteristics of a variable product.', 'wc1c-main'), ['product_id' => $internal_product->getId(), 'external_product_id' => $external_product->getId()]);
			$internal_product->update_meta_data('_wc1c_characteristics', '');
		}

		$raw_attributes = [];

		/** @var AttributesStorageContract $attributes_storage */
		$attributes_storage = Storage::load('attribute');

		/*
		 * Из свойств классификатора
		 */
		if($external_product->hasPropertyValues())
		{
			$this->log()->info(__('Processing of product properties.', 'wc1c-main'));

			$classifier_properties = maybe_unserialize($this->configuration()->getMeta('classifier-properties:' . $reader->getFiletype() . ':' . $reader->catalog->getClassifierId()));

			foreach($external_product->getPropertyValues() as $property_id => $property_value)
			{
				if(empty($property_value['value']))
				{
					$this->log()->info(__('The attribute has an empty value.', 'wc1c-main'), ['property_id' => $property_id, 'value' => $property_value]);
					continue;
				}

				if(!isset($classifier_properties[$property_id]))
				{
					$this->log()->info(__('The attribute was not found in the classifier properties.', 'wc1c-main'), ['property_id' => $property_id, 'value' => $property_value]);
					continue;
				}

				/*
				 * В некоторых случаях приходит пустое значение свойства
				 *
				 * <ЗначенияСвойства>
				 * <Ид>5ff7fc04-d7d8-4c80-b6c6-46fe8bf9ceb2</Ид>
				 * <Значение>00000000-0000-0000-0000-000000000000</Значение>
				 * </ЗначенияСвойства>
				 */
				if($property_value['value'] === '00000000-0000-0000-0000-000000000000')
				{
					$this->log()->info(__('The attribute contains an empty value identifier.', 'wc1c-main'), ['property_id' => $property_id, 'value' => $property_value]);
					continue;
				}

				$property = $classifier_properties[$property_id];
				$global = $attributes_storage->getByLabel($property['name']);
				$attribute_name = $global ? $global->getName() : $property['name'];

				$value = $raw_attributes[$attribute_name]['value'] ?? [];

				if(isset($property['values_variants'][$property_value['value']]))
				{
					$value[] = $property['values_variants'][$property_value['value']];
				}
				else
				{
					// проверка наличия значения, если нет и выключено добавление из значений продуктов - пропускаем
					if($global)
					{
						$default_term = get_term_by('name', $property_value['value'], $global->getTaxonomyName());

						if(!$default_term instanceof \WP_Term && 'yes' !== $this->getOptions('attributes_values_by_product_properties', 'no'))
						{
							$this->log()->notice(__('Adding values from product properties is disabled and the value is missing from the classifier properties directory. Adding a value is skipped.', 'wc1c-main'), ['attribute_name' => $attribute_name, 'value' => $property_value['value']]);
							continue;
						}

						$global->assignValue($property_value['value']);
					}

					$value[] = $property_value['value'];
				}

				$raw_attributes[$attribute_name] =
				[
					'name' => $attribute_name,
					'value' => $value,
					'visible' => 1,
					'taxonomy' => $global ? 1 : 0,
				];
			}

			$internal_product->update_meta_data('_wc1c_properties_import', $raw_attributes);
		}

		/*
		 * Значения характеристик
		 */
		if($external_product->hasCharacteristics())
		{
			$this->log()->info(__('Processing of product characteristics.', 'wc1c-main'));

			/*
			 * Значения других вариаций
			 */
			$old_characteristics = [];

			if(!empty($external_product->getCharacteristicId()))
			{
				$parent_characteristics = (new Factory())->getProduct($internal_product->get_parent_id());
				if($parent_characteristics instanceof VariableProduct)
				{
					$old_characteristics = maybe_unserialize($parent_characteristics->get_meta('_wc1c_characteristics', true));
					if(empty($old_characteristics))
					{
						$old_characteristics = [];
					}
				}
			}

			foreach($external_product->getCharacteristics() as $characteristic_id => $characteristic_value)
			{
				if(empty($characteristic_value['value']))
				{
					$this->log()->info(__('The characteristic has an empty value.', 'wc1c-main'), ['characteristic_id' => $characteristic_id, 'value' => $characteristic_value]);
					continue;
				}

				$old_characteristics[$characteristic_id] = $characteristic_value;

				$global = $attributes_storage->getByLabel($characteristic_value['name']);
				$attribute_name = $global ? $global->getName() : $characteristic_value['name'];

				$value = $raw_attributes[$attribute_name]['value'] ?? [];

				// значение отсутствует в атрибутах
				if(!in_array($characteristic_value['value'], $value, true))
				{
					// проверка наличия значения, если нет и выключено добавление из значений продуктов - пропускаем
					if($global)
					{
						$default_term = get_term_by('name', $characteristic_value['value'], $global->getTaxonomyName());

						if(!$default_term instanceof \WP_Term && 'yes' !== $this->getOptions('products_with_characteristics_use_attributes', 'yes'))
						{
							$this->log()->notice(__('Adding values from product characteristics is disabled and the value is missing in global attributes. Adding a value is skipped.', 'wc1c-main'), ['attribute_name' => $attribute_name, 'value' => $characteristic_value['value']]);
							continue;
						}

						$global->assignValue($characteristic_value['value']);
					}

					$value[] = $characteristic_value['value'];
				}

				// добавление атрибута
				$raw_attributes[$attribute_name] =
				[
					'name' => $attribute_name,
					'value' => $value,
					'visible' => 1,
					'variation' => 1,
					'taxonomy' => $global ? 1 : 0,
				];
			}

			if(!empty($external_product->getCharacteristicId()) && $parent_characteristics instanceof VariableProduct)
			{
				$parent_characteristics->update_meta_data('_wc1c_characteristics', $old_characteristics);
				$parent_characteristics->save();
			}
		}

		/**
		 * Фильтрация перед добавлением по внешним алгоритмам
		 *
		 * @param array $raw_attributes Атрибуты
		 * @param ProductContract $product Данные продукта
		 * @param ProductDataContract $product Данные продукта CML
		 * @param string $mode Режим продукта - создание или обновление
		 * @param Reader $reader Текущий итератор
		 *
		 * @return int|false
		 */
		if(has_filter('wc1c_schema_productscml_assign_products_item_attributes_raw'))
		{
			$raw_attributes = apply_filters('wc1c_schema_productscml_assign_products_item_attributes_raw', $raw_attributes, $internal_product, $external_product, $mode, $reader);
		}

		$this->log()->debug(__('Attributes before processing.', 'wc1c-main'), ['raw_attributes' => $raw_attributes, 'filetype' => $reader->getFiletype()]);

		if($internal_product->isType('variation'))
		{
			$this->setVariationAttributes($internal_product, $raw_attributes);
		}
		else
		{
			$this->setProductAttributes($internal_product, $raw_attributes);
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта: атрибуты
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 * @throws Exception
	 */
	public function assignOffersItemAttributes(ProductContract $internal_product, ProductDataContract $external_product, Reader $reader): ProductContract
	{
		$this->log()->debug(__('Assigning attributes to a product based on the properties of the offers package.', 'wc1c-main'), ['filetype' => $reader->getFiletype(), 'internal_product_id' => $internal_product->getId()]);

		if($reader->getFiletype() !== 'offers')
		{
			return $internal_product;
		}

		/** @var AttributesStorageContract $attributes_storage */
		$attributes_storage = Storage::load('attribute');

		$raw_attributes = [];

		$parent_characteristics = false;
		if($internal_product->get_parent_id() !== 0)
		{
			$parent_characteristics = (new Factory())->getProduct($internal_product->get_parent_id()); // todo: cache
		}

		/*
		 * Из свойств классификатора
		 */
		if($external_product->hasPropertyValues())
		{
			$this->log()->info(__('Processing of product properties.', 'wc1c-main'));

			$property_values_from_characteristics = [];
			if($external_product->hasCharacteristics())
			{
				$property_values_from_characteristics = $external_product->getCharacteristics();
			}

			$classifier_properties = maybe_unserialize($this->configuration()->getMeta('classifier-properties:' . $reader->getFiletype() . ':' . $reader->offers_package->getClassifierId()));

			foreach($external_product->getPropertyValues() as $property_id => $property_value)
			{
				if(empty($property_value['value']))
				{
					$this->log()->info(__('The attribute has an empty value.', 'wc1c-main'), ['property_id' => $property_id, 'value' => $property_value]);
					continue;
				}

				if(!isset($classifier_properties[$property_id]))
				{
					$this->log()->info(__('The attribute was not found in the classifier properties.', 'wc1c-main'), ['property_id' => $property_id, 'value' => $property_value]);
					continue;
				}

				/*
				 * В некоторых случаях приходит пустое значение свойства
				 *
				 * <ЗначенияСвойства>
				 * <Ид>5ff7fc04-d7d8-4c80-b6c6-46fe8bf9ceb2</Ид>
				 * <Значение>00000000-0000-0000-0000-000000000000</Значение>
				 * </ЗначенияСвойства>
				 */
				if($property_value['value'] === '00000000-0000-0000-0000-000000000000')
				{
					$this->log()->info(__('The attribute contains an empty value identifier.', 'wc1c-main'), ['property_id' => $property_id, 'value' => $property_value]);
					continue;
				}

				$found_key = array_search($property_id, array_column($property_values_from_characteristics, 'id'), true);
				if($found_key)
				{
					$this->log()->info(__('The attribute contains in products characteristics.', 'wc1c-main'), ['property_id' => $property_id, 'found_key' => $found_key]);
					continue;
				}

				$property = $classifier_properties[$property_id];
				$global = $attributes_storage->getByLabel($property['name']);
				$attribute_name = $global ? $global->getName() : $property['name'];

				$value = $raw_attributes[$attribute_name]['value'] ?? [];

				if(isset($property['values_variants'][$property_value['value']]))
				{
					$value[] = $property['values_variants'][$property_value['value']];
				}
				else
				{
					// проверка наличия значения, если нет и выключено добавление из значений продуктов - пропускаем
					if($global)
					{
						$default_term = get_term_by('name', $property_value['value'], $global->getTaxonomyName());

						if(!$default_term instanceof \WP_Term && 'yes' !== $this->getOptions('attributes_values_by_product_properties', 'no'))
						{
							$this->log()->notice(__('Adding values from product properties is disabled and the value is missing from the classifier properties directory. Adding a value is skipped.', 'wc1c-main'), ['attribute_name' => $attribute_name, 'value' => $property_value['value']]);
							continue;
						}

						$global->assignValue($property_value['value']);
					}
				}

				$raw_attributes[$attribute_name] =
				[
					'name' => $attribute_name,
					'value' => $value,
					'visible' => 1,
					'taxonomy' => $global ? 1 : 0,
				];
			}
		}

		/*
		 * Значения характеристик
		 */
		if($external_product->hasCharacteristics() && !empty($external_product->getCharacteristicId()))
		{
			$this->log()->info(__('Processing of product characteristics.', 'wc1c-main'));

			/*
			 * Значения других вариаций
			 */
			$old_characteristics = [];

			if($parent_characteristics instanceof VariableProduct)
			{
				$old_characteristics = maybe_unserialize($parent_characteristics->get_meta('_wc1c_characteristics', true));
				if(empty($old_characteristics))
				{
					$old_characteristics = [];
				}
			}

			foreach($external_product->getCharacteristics() as $characteristic_id => $characteristic_value)
			{
				if(empty($characteristic_value['value']))
				{
					$this->log()->info(__('The characteristic has an empty value.', 'wc1c-main'), ['characteristic_id' => $characteristic_id, 'value' => $characteristic_value]);
					continue;
				}

				$old_characteristics[] = $characteristic_value;

				$global = $attributes_storage->getByLabel($characteristic_value['name']);
				$attribute_name = $global ? $global->getName() : $characteristic_value['name'];

				$value = $raw_attributes[$attribute_name]['value'] ?? [];

				// значение отсутствует в атрибутах
				if(!in_array($characteristic_value['value'], $value, true))
				{
					// проверка наличия значения, если нет и выключено добавление из значений продуктов - пропускаем
					if($global)
					{
						$default_term = get_term_by('name', $characteristic_value['value'], $global->getTaxonomyName());

						if(!$default_term instanceof \WP_Term && 'yes' !== $this->getOptions('products_with_characteristics_use_attributes', 'yes'))
						{
							$this->log()->notice(__('Adding values from product characteristics is disabled and the value is missing in global attributes. Adding a value is skipped.', 'wc1c-main'), ['attribute_name' => $attribute_name, 'value' => $characteristic_value['value']]);
							continue;
						}

						$global->assignValue($characteristic_value['value']);
					}

					$value[] = $characteristic_value['value'];
				}

				// добавление атрибута
				$raw_attributes[$attribute_name] =
				[
					'name' => $attribute_name,
					'value' => $value,
					'visible' => 1,
					'variation' => 1,
					'taxonomy' => $global ? 1 : 0,
				];
			}

			if($parent_characteristics instanceof VariableProduct)
			{
				$import_characteristics = maybe_unserialize($parent_characteristics->get_meta('_wc1c_properties_import', true));
				if(!is_array($import_characteristics) )
				{
					$import_characteristics = [];
				}

				$parent_attr = array_merge($import_characteristics, $raw_attributes);

				foreach($old_characteristics as $characteristic_id => $characteristic_value)
				{
					if(empty($characteristic_value['value']))
					{
						$this->log()->info(__('The characteristic has an empty value.', 'wc1c-main'), ['characteristic_id' => $characteristic_id, 'value' => $characteristic_value]);
						continue;
					}

					$global = $attributes_storage->getByLabel($characteristic_value['name']);
					$attribute_name = $global ? $global->getName() : $characteristic_value['name'];

					$value = $parent_attr[$attribute_name]['value'] ?? [];

					// значение отсутствует в атрибутах
					if(!in_array($characteristic_value['value'], $value, true))
					{
						// проверка наличия значения, если нет и выключено добавление из значений продуктов - пропускаем
						if($global)
						{
							$default_term = get_term_by('name', $characteristic_value['value'], $global->getTaxonomyName());

							if(!$default_term instanceof \WP_Term && 'yes' !== $this->getOptions('attributes_values_by_product_characteristics', 'yes'))
							{
								$this->log()->notice(__('Adding values from product characteristics is disabled and the value is missing in global attributes. Adding a value is skipped.', 'wc1c-main'), ['attribute_name' => $attribute_name, 'value' => $characteristic_value['value']]);
								continue;
							}

							$global->assignValue($characteristic_value['value']);
						}

						$value[] = $characteristic_value['value'];
					}

					// добавление атрибута
					$parent_attr[$attribute_name] =
					[
						'name' => $attribute_name,
						'value' => $value,
						'visible' => 1,
						'variation' => 1,
						'taxonomy' => $global ? 1 : 0,
					];
				}

				$this->setProductAttributes($parent_characteristics, $parent_attr);
				$parent_characteristics->update_meta_data('_wc1c_characteristics', $old_characteristics);
				$parent_characteristics->save();
			}
		}

		/**
		 * Фильтрация перед добавлением по внешним алгоритмам
		 *
		 * @param array $raw_attributes Атрибуты
		 * @param ProductContract $product Данные продукта
		 * @param ProductDataContract $product Данные продукта CML
		 * @param Reader $reader Текущий итератор
		 *
		 * @return int|false
		 */
		if(has_filter('wc1c_schema_productscml_assign_offers_item_attributes_raw'))
		{
			$raw_attributes = apply_filters('wc1c_schema_productscml_assign_offers_item_attributes_raw', $raw_attributes, $internal_product, $external_product, $reader);

			if(empty($raw_attributes))
			{
				return $internal_product;
			}
		}

		$this->log()->debug(__('Attributes before processing.', 'wc1c-main'), ['raw_attributes' => $raw_attributes, 'filetype' => $reader->getFiletype()]);

		if(empty($raw_attributes))
		{
			$this->log()->info(__('Attributes not found. Skipped.', 'wc1c-main'), ['filetype' => $reader->getFiletype()]);
			return $internal_product;
		}

		if($internal_product->isType('variation'))
		{
			$this->setVariationAttributes($internal_product, $raw_attributes);
		}
		else
		{
			$this->setProductAttributes($internal_product, $raw_attributes);
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта по данным предложений: цены
	 *
	 * @param ProductContract $internal_product Экземпляр продукта
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignOffersItemPrices(ProductContract $internal_product, ProductDataContract $external_product, Reader $reader): ProductContract
	{
		$this->log()->debug(__('Prices processing.', 'wc1c-main'), ['filetype' => $reader->getFiletype(), 'product_id' => $internal_product->getId(), 'offer_id' => $external_product->getId(), 'offer_characteristic_id' => $external_product->getCharacteristicId()]);

		if(false === $external_product->hasPrices())
		{
			$this->log()->info(__('Prices is not found. Update skipping.', 'wc1c-main'));
			return $internal_product;
		}

		$prices = $external_product->getPrices();

		$this->log()->debug(__('Prices before processing.', 'wc1c-main'), ['prices' => $prices]);

		$regular = $this->getOptions('products_prices_regular_by_cml', 'no');
		$sale = $this->getOptions('products_prices_sale_by_cml', 'no');

		$regular_value = '';
		$sale_value = '';

		if('no' !== $regular)
		{
			switch($regular)
			{
				case 'yes_name':
					$price_types = $reader->offers_package->getPriceTypes();
					$regular_price_name = $this->getOptions('products_prices_regular_by_cml_from_name', '');
					$regular_price_id = '';

					foreach($price_types as $price_type)
					{
						if($price_type['name'] === $regular_price_name)
						{
							$regular_price_id = $price_type['guid'];
							break;
						}
					}

					if('' !== $regular_price_id && isset($prices[$regular_price_id]))
					{
						$regular_value = $prices[$regular_price_id]['price'];
						unset($prices[$regular_price_id]);
					}
					break;
				default:
					$first_value = reset($prices);
					$regular_value = $first_value['price'];
					unset($prices[$first_value['price_type_id']]);
			}

			$this->log()->debug(__('Assign the regular price.', 'wc1c-main'), ['regular_value' => $regular_value]);
			$internal_product->set_regular_price($regular_value);
		}

		if('no' !== $sale)
		{
			switch($sale)
			{
				case 'yes_name':
					$price_types = $reader->offers_package->getPriceTypes();
					$sale_price_name = $this->getOptions('products_prices_sale_by_cml_from_name', '');
					$sale_price_id = '';

					foreach($price_types as $price_type)
					{
						if($price_type['name'] === $sale_price_name)
						{
							$sale_price_id = $price_type['guid'];
							break;
						}
					}

					if('' !== $sale_price_id && isset($prices[$sale_price_id]))
					{
						$sale_value = $prices[$sale_price_id]['price'];
						unset($prices[$sale_price_id]);
					}
					break;
				default:
					$first_value = reset($prices);
					$sale_value = $first_value['price'];
			}

			$this->log()->debug(__('Assign the sale price.', 'wc1c-main'), ['sale_value' => $sale_value]);
			$internal_product->set_sale_price($sale_value);
		}

		if($regular !== 'no' || $sale !== 'no')
		{
			if(!empty($sale_value) && $sale_value < $regular_value)
			{
				$this->log()->debug(__('Assign the current price from sale price.', 'wc1c-main'), ['sale_value' => $sale_value]);
				$internal_product->set_price($sale_value);
			}
			else
			{
				$this->log()->debug(__('Assign the current price from regular price.', 'wc1c-main'), ['regular_value' => $regular_value]);
				$internal_product->set_price($regular_value);
			}
		}

		if($regular === 'no' && $sale === 'no')
		{
			$this->log()->info(__('Prices processing is off. Assigning is skip.', 'wc1c-main'));
		}
		else
		{
			$this->log()->debug(__('Prices processing is successful.', 'wc1c-main'), ['regular_value' => $regular_value, 'sale_value' => $sale_value]);
		}

		return $internal_product;
	}

	/**
	 * Назначение данных продукта по данным предложений: запасы
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 */
	public function assignOffersItemInventories(ProductContract $internal_product, ProductDataContract $external_product, Reader $reader): ProductContract
	{
		$this->log()->debug(__('Inventories processing.', 'wc1c-main'), ['filetype' => $reader->getFiletype(), 'product_id' => $internal_product->getId(), 'offer_id' => $external_product->getId(), 'offer_characteristic_id' => $external_product->getCharacteristicId()]);

		if('yes' !== $this->getOptions('products_inventories_by_offers_quantity', 'no'))
		{
			$this->log()->info(__('Product inventories update by offers quantity is disabled. Update skipping.', 'wc1c-main'));
			return $internal_product;
		}

		$this->log()->debug(__('Set inventories by offers quantity: start.', 'wc1c-main'));

		/**
		 * Вариация:
		 * - проверить остатки родителя
		 * -- если есть, пропуск обработки запасов на уровне вариаций
		 * -- если нет, то как обычно
		 */
		if($internal_product->isType('variation'))
		{
			$internal_product_parent = (new Factory())->getProduct($internal_product->get_parent_id());

			$parent_quantity = $internal_product_parent->get_stock_quantity();

			if($parent_quantity && $parent_quantity > 0)
			{
				$this->log()->info(__('Product inventories stored in parent product. Update variation skipping.', 'wc1c-main'));
				return $internal_product;
			}
		}

		if(false === $internal_product->get_manage_stock())
		{
			$this->log()->debug(__('Inventory management at the product level is disabled. Enabling.', 'wc1c-main'));
			$internal_product->set_manage_stock(true);
		}

		$product_quantity = $external_product->getQuantity();

		$stock_status = $product_quantity > 0 ? 'instock' : 'outofstock';

		wc_update_product_stock($internal_product, $product_quantity, 'set');

		$internal_product->set_stock_status($stock_status);

		$this->log()->debug(__('Set inventories by offers quantity: end.', 'wc1c-main'), ['quantity' => $product_quantity]);

		return $internal_product;
	}

	/**
	 * Обработка данных продукта (товара) из каталога товаров, данные могут быть как продуктом, так и характеристикой.
	 *
	 * @param $external_product ProductDataContract
	 * @param $reader Reader
	 *
	 * @return void
	 * @throws Exception
	 */
	public function processingProductsItem(ProductDataContract $external_product, Reader $reader)
	{
		$this->log()->info(__('Processing a product from a catalog of products.', 'wc1c-main'), ['product_id' => $external_product->getId(), 'product_characteristic_id' => $external_product->getCharacteristicId()]);

		$product_id = 0;
		$product_factory = new Factory();

		/*
		 * Поиск продукта по идентификатору 1С
		 */
		if('yes' === $this->getOptions('product_sync_by_id', 'yes'))
		{
			$product_id = $product_factory->findIdsByExternalIdAndCharacteristicId($external_product->getId(), $external_product->getCharacteristicId());

			$this->log()->debug(__('Product search result by external code from 1C.', 'wc1c-main'), ['product_ids' => $product_id]);

			if(is_array($product_id)) // todo: обработка нескольких?
			{
				$this->log()->notice(__('Several identical products were found. The first one is selected.', 'wc1c-main'), ['product_ids' => $product_id]);
				$product_id = reset($product_id);
			}
		}

		/**
		 * Поиск идентификатора существующего продукта по внешним алгоритмам
		 *
		 * @param int $product_id Идентификатор найденного продукта
		 * @param ProductDataContract $external_product Данные продукта в CML
		 * @param SchemaAbstract $this
		 * @param Reader $reader Текущий итератор
		 *
		 * @return int|false
		 */
		if(empty($product_id) && has_filter('wc1c_schema_productscml_processing_products_search'))
		{
			$product_id = apply_filters('wc1c_schema_productscml_processing_products_search', $product_id, $external_product, $this, $reader);

			$this->log()->debug(__('Product search result by external algorithms.', 'wc1c-main'), ['product_ids' => $product_id]);
		}

		/**
		 * Ни один продукт не найден
		 */
		if(empty($product_id))
		{
			$this->log()->info(__('Product is not found.', 'wc1c-main'));

			/*
			 * Создание продуктов отключено
			 */
			if('yes' !== $this->getOptions('products_create', 'no'))
			{
				$this->log()->info(__('Products create is disabled. Product create skipped.', 'wc1c-main'));
				return;
			}

			/*
			 * Пропуск создания продуктов помеченных к удалению в 1С
			 */
			$raw = $external_product->getData(); // todo: вынести в метод
			if(isset($raw['delete_mark']) && $raw['delete_mark'] === 'yes' && 'yes' !== $this->getOptions('products_create_delete_mark', 'no'))
			{
				$this->log()->info(__('The use of products delete mark is disabled. Processing skipped.', 'wc1c-main'));
				return;
			}
			unset($raw);

			/*
			 * Продукт с характеристикой
			 * 1. Проверяем родительский продукт
			 * - Если нет, и включено создание родительского продукта по данным характеристики - создаем вариативный
			 * - Иначе создаем простой продукт с назначением ему характеристики
			 */
			if($external_product->hasCharacteristicId())
			{
				$this->log()->info(__('The product contains the characteristics.', 'wc1c-main')); // todo: реализация
			}
			else
			{
				$this->log()->info(__('The product is simple. Create.', 'wc1c-main'));

				/**
				 * Создание простого продукта с заполнением данных
				 *
				 * @var $internal_product ProductContract
				 */
				$internal_product = new SimpleProduct();

				$internal_product->setSchemaId($this->getId());
				$internal_product->setConfigurationId($this->configuration()->getId());
				$internal_product->setExternalId($external_product->getId());
			}

			/**
			 * Назначение данных создаваемого продукта по внешним алгоритмам перед сохранением
			 *
			 * @param ProductContract $internal_product Экземпляр создаваемого продукта
			 * @param ProductDataContract $external_product Данные продукта в CML
			 * @param string $mode Режим назначения данных
			 * @param Reader $reader Текущий итератор
			 *
			 * @return ProductContract
			 */
			if(has_filter('wc1c_schema_productscml_processing_products_item_before_save'))
			{
				$internal_product = apply_filters('wc1c_schema_productscml_processing_products_item_before_save', $internal_product, $external_product, 'create', $reader);
			}

			try
			{
				$id = $internal_product->save();
				$this->log()->info(__('The product is created.', 'wc1c-main'), ['product_id' => $id, 'product_type' => $internal_product->get_type()]);
			}
			catch(\Exception $e)
			{
				throw new Exception($e->getMessage());
			}

			/**
			 * Назначение данных создаваемого продукта по внешним алгоритмам после сохранения
			 *
			 * @param ProductContract $internal_product Экземпляр создаваемого продукта
			 * @param ProductDataContract $external_product Данные продукта в CML
			 * @param string $mode Режим назначения данных
			 * @param Reader $reader Текущий итератор
			 *
			 * @return ProductContract
			 */
			if(has_filter('wc1c_schema_productscml_processing_products_item_after_save'))
			{
				$internal_product = apply_filters('wc1c_schema_productscml_processing_products_item_after_save', $internal_product, $external_product, 'create', $reader);

				try
				{
					$id = $internal_product->save();
					$this->log()->info(__('The product has been updated using external algorithms.', 'wc1c-main'), ['product_id' => $id, 'product_type' => $internal_product->get_type()]);
				}
				catch(\Exception $e)
				{
					throw new Exception($e->getMessage());
				}
			}

			return;
		}

		/**
		 * Обновление существующих продуктов отключено
		 */
		if('yes' !== $this->getOptions('products_update', 'no'))
		{
			$this->log()->info(__('Products update is disabled. Product update skipped.', 'wc1c-main'), ['product_id' => $product_id]);
			return;
		}

		/*
		 * Экземпляр обновляемого продукта по найденному идентификатору продукта
		 */
		$update_product = $product_factory->getProduct($product_id);

		/*
		 * Пропуск продуктов созданных из других конфигураций
		 */
		if('yes' === $this->getOptions('products_update_only_configuration', 'no') && (int)$update_product->getConfigurationId() !== $this->configuration()->getId())
		{
			$this->log()->info(__('The product is created from a different configuration. Update skipped.', 'wc1c-main'), ['product_id' => $product_id]);
			return;
		}

		/*
		 * Пропуск продуктов созданных из других схем
		 */
		if('yes' === $this->getOptions('products_update_only_schema', 'no') && (string)$update_product->getSchemaId() !== $this->getId())
		{
			$this->log()->info(__('The product is created from a different schema. Update skipped.', 'wc1c-main'), ['product_id' => $product_id]);
			return;
		}

		/*
		 * Пропуск обновления продуктов из корзины, не помеченных к удалению в 1С
		 */
		$raw = $external_product->getData(); // todo: вынести в метод
		if
		(
			'trash' === $update_product->get_status() &&
			isset($raw['delete_mark']) && $raw['delete_mark'] === 'no'
		   && 'yes' !== $this->getOptions('products_update_use_delete_mark', 'no')
		)
		{
			$this->log()->info(__('The use of products from trash is disabled. Processing skipped.', 'wc1c-main'));
			return;
		}
		unset($raw);

		/**
		 * Назначение данных обновляемого продукта по внешним алгоритмам перед сохранением
		 *
		 * @param ProductContract $internal_product Экземпляр обновляемого продукта
		 * @param ProductDataContract $external_product Данные продукта в CML
		 * @param string $mode Режим назначения данных
		 * @param Reader $reader Текущий итератор
		 *
		 * @return ProductContract
		 */
		if(has_filter('wc1c_schema_productscml_processing_products_item_before_save'))
		{
			$update_product = apply_filters('wc1c_schema_productscml_processing_products_item_before_save', $update_product, $external_product, 'update', $reader);
		}

		try
		{
			$update_product->save();
		}
		catch(\Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		/**
		 * Назначение данных обновляемого продукта по внешним алгоритмам после сохранения
		 *
		 * @param ProductContract $internal_product Экземпляр обновляемого продукта
		 * @param ProductDataContract $external_product Данные продукта в CML
		 * @param string $mode Режим назначения данных
		 * @param Reader $reader Текущий итератор
		 *
		 * @return ProductContract
		 */
		if(has_filter('wc1c_schema_productscml_processing_products_item_after_save'))
		{
			$update_product = apply_filters('wc1c_schema_productscml_processing_products_item_after_save', $update_product, $external_product, 'update', $reader);

			try
			{
				$update_product->save();
			}
			catch(\Exception $e)
			{
				throw new Exception($e->getMessage());
			}
		}
	}

	/**
	 * Обработка элементов пакета предложений. Данные могут быть как продуктом, так и характеристикой.
	 *
	 * @param ProductDataContract $external_offer
	 * @param Reader $reader
	 *
	 * @return void
	 * @throws Exception
	 */
	public function processingOffersItem(ProductDataContract $external_offer, Reader $reader)
	{
		$this->log()->info(__('Processing an offer from a package of offers.', 'wc1c-main'), ['offer_id' => $external_offer->getId(), 'offer_characteristic_id' => $external_offer->getCharacteristicId()]);

		$product_factory = new Factory();

		$internal_offer_id = $product_factory->findIdsByExternalIdAndCharacteristicId($external_offer->getId(), $external_offer->getCharacteristicId());

		if(is_array($internal_offer_id)) // todo: обработка нескольких?
		{
			$this->log()->notice(__('Several identical products were found. The first one is selected.', 'wc1c-main'), ['product_ids' => $internal_offer_id]);
			$internal_offer_id = reset($internal_offer_id);
		}

		/**
		 * Поиск идентификатора существующего продукта по внешним алгоритмам
		 *
		 * @param int $internal_offer_id Идентификатор найденного продукта
		 * @param ProductDataContract $external_offer Данные продукта в CML
		 * @param Reader $reader Текущий итератор
		 *
		 * @return int|false
		 */
		if(empty($internal_offer_id) && has_filter('wc1c_schema_productscml_processing_offers_search'))
		{
			$internal_offer_id = apply_filters('wc1c_schema_productscml_processing_offers_search', $internal_offer_id, $external_offer, $reader);
		}

		/*
		 * Привет. Как дела? Что бы было не хуже, вот пояснения.
		 *
		 * Если продукт не найден, пришла характеристика и включено использование характеристик на основе пакета предложений
		 * - проверяем родительский продукт
		 * -- если найден, превращаем его в вариативный
		 * -- добавляем вариацию с присвоением идентификатора характеристики
		 * -- запускаем алгоритмы обновления вариаций
		 * -- если не найден, и включено создание на основе первой характеристики - создаем
		 */

		if(empty($internal_offer_id) && empty($external_offer->getCharacteristicId()))
		{
			$this->log()->notice(__('Product not found. Offer update skipped.', 'wc1c-main'), ['offer' => $external_offer]);
			return;
		}

		/**
		 * Родительский продукт
		 */
		$internal_parent_offer_id = 0;
		if(!empty($external_offer->getCharacteristicId()))
		{
			$internal_parent_offer_id = $product_factory->findIdsByExternalIdAndCharacteristicId($external_offer->getId(), '');

			if(is_array($internal_parent_offer_id)) // todo: обработка нескольких?
			{
				$this->log()->warning(__('Several identical parent of products were found. The first one is selected.', 'wc1c-main'), ['product_ids' => $internal_offer_id]);
				$internal_parent_offer_id = reset($internal_parent_offer_id);
			}

			/*
			 * Родительский продукт не найден
			 */
			if(0 === $internal_parent_offer_id)
			{
				$this->log()->notice(__('Product parent not found. Offer update skipped.', 'wc1c-main'), ['offer' => $external_offer]);
				return;
			}

			$internal_product_parent = $product_factory->getProduct($internal_parent_offer_id);

			/*
			 * Продукт не вариативный, превращаем его в вариативный
			 */
			if(!$internal_product_parent instanceof VariableProduct)
			{
				$this->log()->debug(__('Changing the product type to variable.', 'wc1c-main'), ['product_id' => $internal_parent_offer_id]);

				$internal_product_parent = new VariableProduct($internal_parent_offer_id);
				$internal_parent_offer_id = $internal_product_parent->save();
			}
		}

		/*
		 * Экземпляр обновляемого продукта по найденному идентификатору продукта
		 */
		if($internal_offer_id)
		{
			$internal_offer = $product_factory->getProduct($internal_offer_id);
		}
		else
		{
			if(empty($internal_parent_offer_id))
			{
				$this->log()->warning(__('The parent product was not found. The creation of the variation is skipped.', 'wc1c-main'), ['offer_id' => $internal_offer_id]);
				return;
			}

			$this->log()->debug(__('Variation is not found. Creating.', 'wc1c-main'), ['product_id' => $internal_parent_offer_id]);

			$internal_offer = new VariationVariableProduct();

			$internal_offer->set_parent_id($internal_parent_offer_id);
			$internal_offer->setSchemaId($this->getId());
			$internal_offer->setConfigurationId($this->configuration()->getId());
			$internal_offer->setExternalId($external_offer->getId());
			$internal_offer->setExternalCharacteristicId($external_offer->getCharacteristicId());

			$internal_offer_id = $internal_offer->save();
			$this->log()->debug(__('The creation of the variation is completed.', 'wc1c-main'), ['product_variation_id' => $internal_offer_id]);
		}

		/*
		 * Пропуск продуктов созданных из других конфигураций
		 */
		if('yes' === $this->getOptions('products_update_only_configuration', 'no') && (int)$internal_offer->getConfigurationId() !== $this->configuration()->getId())
		{
			$this->log()->info(__('The product is created from a different configuration. Update skipped.', 'wc1c-main'), ['offer_id' => $internal_offer_id]);
			return;
		}

		/*
		 * Пропуск продуктов созданных из других схем
		 */
		if('yes' === $this->getOptions('products_update_only_schema', 'no') && (string)$internal_offer->getSchemaId() !== $this->getId())
		{
			$this->log()->info(__('The product is created from a different schema. Update skipped.', 'wc1c-main'), ['offer_id' => $internal_offer_id]);
			return;
		}

		/**
		 * Назначение данных обновляемого продукта по внешним алгоритмам перед сохранением
		 *
		 * @param ProductContract $internal_offer Экземпляр обновляемого продукта
		 * @param ProductDataContract $external_offer Данные продукта в CML
		 * @param Reader $reader Текущий итератор
		 *
		 * @return ProductContract
		 */
		if(has_filter('wc1c_schema_productscml_processing_offers_item_before_save'))
		{
			$internal_offer = apply_filters('wc1c_schema_productscml_processing_offers_item_before_save', $internal_offer, $external_offer, $reader);
		}

		try
		{
			$internal_offer->save();
		}
		catch(\Exception $e)
		{
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Обработка пакета предложений
	 *
	 * @param Reader $reader
	 *
	 * @return void
	 * @throws Exception
	 */
	public function processingOffers(Reader $reader)
	{
		$types =
		[
			'offers',
			'rests',
			'prices',
		];

		if(!in_array($reader->getFiletype(), $types))
		{
			return;
		}
		if(is_null($reader->offers_package))
		{
			$reader->offers_package = new OffersPackage();
		}

		if($reader->nodeName === 'ПакетПредложений' && $reader->xml_reader->nodeType === XMLReader::ELEMENT)
		{
			$only_changes = $reader->xml_reader->getAttribute('СодержитТолькоИзменения') ?: true;
			if($only_changes === 'false')
			{
				$only_changes = false;
			}
			$reader->offers_package->setOnlyChanges($only_changes);
		}

		if($reader->parentNodeName === 'ПакетПредложений' && $reader->xml_reader->nodeType === XMLReader::ELEMENT)
		{
			switch($reader->nodeName)
			{
				case 'Ид':
					$reader->offers_package->setId($reader->xml_reader->readString());
					break;
				case 'Наименование':
					$reader->offers_package->setName($reader->xml_reader->readString());
					break;
				case 'ИдКаталога':
					$reader->offers_package->setCatalogId($reader->xml_reader->readString());
					break;
				case 'ИдКлассификатора':
					$reader->offers_package->setClassifierId($reader->xml_reader->readString());
					break;
				case 'Владелец':
					$owner = $reader->decoder()->process('counterparty', $reader->xml_reader->readOuterXml());
					$reader->offers_package->setOwner($owner);
					$reader->next();
					break;
				case 'ТипыЦен':
					$price_types = $reader->decoder()->process('price_types', $reader->xml_reader->readOuterXml());
					$reader->offers_package->setPriceTypes($price_types);
					$reader->next();
					break;
			}
		}

		if($reader->parentNodeName === 'Предложения' && $reader->nodeName === 'Предложение' && $reader->xml_reader->nodeType === XMLReader::ELEMENT)
		{
			$offer = $reader->decoder->process('offer', $reader->xml_reader->readOuterXml());

			if(has_filter('wc1c_schema_productscml_processing_offers'))
			{
				$offer = apply_filters('wc1c_schema_productscml_processing_offers', $offer, $reader, $this);
			}

			if(!$offer instanceof ProductDataContract)
			{
				return;
			}

			/*
			 * Пропуск продуктов с характеристиками
			 */
			if(true === $offer->hasCharacteristicId() && 'yes' !== $this->getOptions('products_with_characteristics', 'no'))
			{
				$this->log()->info(__('The use of products with characteristics is disabled. Processing skipped.', 'wc1c-main'));
				return;
			}

			try
			{
				do_action('wc1c_schema_productscml_processing_offers_item', $offer, $reader, $this);
			}
			catch(Exception $e)
			{
				$this->log()->warning(__('An exception was thrown while processing the offer.', 'wc1c-main'), ['exception' => $e]);
			}

			$reader->next();
		}
	}
}
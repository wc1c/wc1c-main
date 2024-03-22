<?php namespace Wc1c\Main\Schemas\Productscml;

defined('ABSPATH') || exit;

use SimpleXMLElement;
use Wc1c\Main\Exceptions\TimerException;
use Wc1c\Main\Traits\UtilityTrait;
use Wc1c\Wc\Entities\Image;
use Wc1c\Wc\Products\AttributeProduct;
use Wc1c\Cml\Contracts\ClassifierDataContract;
use Wc1c\Cml\Contracts\ProductDataContract;
use Wc1c\Cml\Decoder;
use Wc1c\Cml\Entities\Catalog;
use Wc1c\Cml\Entities\OffersPackage;
use Wc1c\Cml\Reader;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Schemas\Abstracts\SchemaAbstract;
use Wc1c\Wc\Contracts\AttributeContract;
use Wc1c\Wc\Contracts\AttributesStorageContract;
use Wc1c\Wc\Contracts\CategoriesStorageContract;
use Wc1c\Wc\Contracts\ImagesStorageContract;
use Wc1c\Wc\Contracts\ProductContract;
use Wc1c\Wc\Entities\Attribute;
use Wc1c\Wc\Entities\Category;
use Wc1c\Wc\Products\Factory;
use Wc1c\Wc\Products\SimpleProduct;
use Wc1c\Wc\Products\VariableProduct;
use Wc1c\Wc\Products\VariationVariableProduct;
use Wc1c\Wc\Storage;
use XMLReader;

/**
 * Core
 *
 * @package Wc1c\Main\Schemas\Productscml
 */
class Core extends SchemaAbstract
{
	use UtilityTrait;

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
		$this->setVersion('0.15.0');

		$this->setName(__('Products data exchange via CommerceML', 'wc1c-main'));
		$this->setDescription(__('Creating and updating products (goods) in WooCommerce according to data from 1C using the CommerceML protocol of different versions.', 'wc1c-main'));
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

		$this->setInitialized(true);

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
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemTaxesClass'], 100, 4);
			add_filter('wc1c_schema_productscml_processing_products_item_before_save', [$this, 'assignProductsItemTaxesStatus'], 100, 4);

			add_filter('wc1c_schema_productscml_processing_products_item_after_save', [$this, 'assignProductsItemImages'], 10, 4);

			add_filter('wc1c_schema_productscml_processing_offers_item_before_save', [$this, 'assignOffersItemAttributes'], 10, 3);
			add_filter('wc1c_schema_productscml_processing_offers_item_before_save', [$this, 'assignOffersItemPrices'], 10, 3);
			add_filter('wc1c_schema_productscml_processing_offers_item_before_save', [$this, 'assignOffersItemInventories'], 10, 3);
            add_filter('wc1c_schema_productscml_processing_offers_item_before_save', [$this, 'assignOffersItemImages'], 10, 3);
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
		catch(\Throwable $exception)
		{
			$this->log()->error(__('The file cannot be processed. DecoderCML threw an exception.', 'wc1c-main'), ['exception' => $exception]);
			return false;
		}

		if(has_filter('wc1c_schema_productscml_file_processing_decoder'))
		{
			$this->log()->info(__('DecoderCML has been overridden by external algorithms.', 'wc1c-main'));
			$decoder = apply_filters('wc1c_schema_productscml_file_processing_decoder', $decoder, $this);
		}

		try
		{
			$reader = new Reader($file_path, $decoder);
		}
		catch(\Throwable $exception)
		{
			$this->log()->error(__('The file cannot be processed. ReaderCML threw an exception.', 'wc1c-main'), ['exception' => $exception]);
			return false;
		}

		$this->log()->debug(__('Filetype:', 'wc1c-main') . ' ' . $reader->getFiletype(), ['filetype' => $reader->getFiletype()]);

		if(has_filter('wc1c_schema_productscml_file_processing_reader'))
		{
			$this->log()->info(__('ReaderCML has been overridden by external algorithms.', 'wc1c-main'));
			$reader = apply_filters('wc1c_schema_productscml_file_processing_reader', $reader, $this);
		}

		while($reader->read())
		{
			try
			{
				do_action('wc1c_schema_productscml_file_processing_read', $reader, $this);
			}
			catch(\Throwable $e)
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
			throw new TimerException(__('There was not enough time to load all the data.', 'wc1c-main'));
		}
	}

	/**
	 * Receiver
	 *
	 * @return void
	 */
	public function receiver()
	{
		if('no' !== $this->getOptions('ob_end_clean', 'no'))
		{
            $this->log()->debug(__('Clearing the output buffer.', 'wc1c-main'));

            $buffer_status = ob_get_status();

            if(empty($buffer_status))
            {
                unset($buffer_status);
            }
            else
            {
                $content = ob_get_contents();

                if($content !== '')
                {
                    ob_clean();
                    $this->log()->debug(__('Cleaned up data.', 'wc1c-main'), ['data' => $content]);
                }

                unset($content, $buffer_status);
            }

            $this->log()->debug(__('Clearing the output buffer as completed.', 'wc1c-main'));
		}

		if($this->configuration()->isEnabled() === false)
		{
			$message = __('Configuration is offline.', 'wc1c-main');

			wc1c()->log('receiver')->warning($message);
			$this->receiver->sendResponseByType('failure', $message);
		}

		try
		{
			$this->configuration()->setStatus('processing');

			$this->configuration()->setDateActivity(time());
			$this->configuration()->save();
		}
		catch(\Throwable $e)
		{
			$message = __('Error saving configuration.', 'wc1c-main');

			wc1c()->log('receiver')->error($message, ['exception' => $e]);
			$this->receiver->sendResponseByType('failure', $message);
		}

		$action = false;
		$wc1c_receiver_action = 'wc1c_receiver_' . $this->getId();

		if(has_action($wc1c_receiver_action))
		{
			$action = true;

			ob_start();
			nocache_headers();
			do_action($wc1c_receiver_action);
			ob_end_clean();
		}

		if(false === $action)
		{
			$message = __('Receiver request is very bad! Action not found.', 'wc1c-main');

			wc1c()->log('receiver')->warning($message, ['action' => $wc1c_receiver_action]);
			$this->receiver->sendResponseByType('failure', $message);
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
		if(!is_null($reader->classifier))
		{
			return;
		}

		if($reader->nodeName === 'Классификатор' && $reader->isElement())
		{
			$only_changes = $reader->xml_reader->getAttribute('СодержитТолькоИзменения') ?: false;
			if($only_changes === 'true')
			{
				$only_changes = true;
			}

            $classifier_xml = new SimpleXMLElement($reader->xml_reader->readOuterXml());

            try
            {
                $classifier = $reader->decoder()->process('classifier', $classifier_xml);
            }
            catch(\Throwable $e)
            {
                $this->log()->warning(__('DecoderCML threw an exception while converting the classifier.', 'wc1c-main'), ['exception' => $e]);
                return;
            }

			$classifier->setOnlyChanges($only_changes);

			/**
			 * Внешняя обработка классификатора
			 *
			 * @param ClassifierDataContract $classifier
			 * @param SchemaAbstract $this
             * @param SimpleXMLElement $classifier_xml
			 */
			if(has_filter('wc1c_schema_productscml_processing_classifier'))
			{
				$classifier = apply_filters('wc1c_schema_productscml_processing_classifier', $classifier, $this, $classifier_xml);
			}

			if(!$classifier instanceof ClassifierDataContract)
			{
				$this->log()->debug(__('Classifier !instanceof ClassifierDataContract. Processing skipped.', 'wc1c-main'), ['data' => $classifier]);
				return;
			}

			$reader->classifier = $classifier;

			try
			{
				do_action('wc1c_schema_productscml_processing_classifier_item', $classifier, $reader, $this);
			}
			catch(\Throwable $e)
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
		if(!$classifier->hasGroups())
		{
			$this->log()->info(__('Classifier groups is empty.', 'wc1c-main'));
			return;
		}

		$classifier_groups = $classifier->getGroups();

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
			$assign_image = $this->getOptions('categories_classifier_groups_create_assign_image', 'no');

			$update_parent = $this->getOptions('categories_classifier_groups_update_parent', 'yes');
			$update_description = $this->getOptions('categories_classifier_groups_update_description', 'no');
			$update_name = $this->getOptions('categories_classifier_groups_update_name', 'no');
			$update_image = $this->getOptions('categories_classifier_groups_update_image', 'no');

			/** @var CategoriesStorageContract $categories_storage */
			$categories_storage = Storage::load('category');

			if('yes' === $assign_image || 'yes' === $update_image)
			{
				/** @var ImagesStorageContract $images_storage */
				$images_storage = Storage::load('image');
			}

			foreach($classifier_groups as $group_id => $group)
			{
				$this->log()->debug(__('Classifier group processing.', 'wc1c-main'), ['group_id' => $group_id, 'group' => $group]);

				$category = false;

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
                    $this->log()->info(__('Category search by external algorithms.', 'wc1c-main'));

					$category = apply_filters('wc1c_schema_productscml_processing_classifier_groups_category_search', $this, $group, $reader);

                    if(!empty($category))
                    {
                        $this->log()->debug(__('Category search result by external algorithms.', 'wc1c-main'), ['category' => $category]);
                    }
				}

				/*
				 * Поиск категории по идентификатору из классификатора
				 */
				if(empty($category))
				{
					$this->log()->info(__('Category search by group ID from 1C.', 'wc1c-main'), ['group_id' => $group_id]);

					$category = $categories_storage->getByExternalId($group_id);

                    if(!empty($category))
                    {
                        $this->log()->debug(__('Category search result by group ID from 1C.', 'wc1c-main'), ['category' => $category]);
                    }
				}

				/**
				 * Найдено несколько категорий с одним и тем же идентификатором из классификатора
				 */
				if(!empty($category) && is_array($category))
				{
					$category = $category[0];

					$cats_data =[];
					foreach($category as $category_key => $category_obj)
					{
						$cats_data[$category_key] = $category_obj->getData();
					}

					$this->log()->warning(__('More than one category found by ID from 1C. Assigning the first available.', 'wc1c-main'), ['categories' => $cats_data]);
				}

				/**
				 * Категория не найдена, но включено использование существующих
				 */
				if(!$category instanceof Category && 'no' !== $merge_categories)
				{
					$this->log()->debug(__('Category not found. Trying to use existing categories.', 'wc1c-main'), ['group_id' => $group_id]);

					$cats = [];
					$category_merge = false;

					$category = $categories_storage->getByName($group['name']);

					if(false === $category)
					{
						$this->log()->info(__('No category found for the specified name.', 'wc1c-main'), ['category_name' => $group['name']]);
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
								$this->log()->debug(__('Category found by name without nesting.', 'wc1c-main'), ['category' => $cat->getData()]);

								$category_merge = true;
								$category = $cat;
								break;
							}

							/*
							 * С учетом родительской категории
							 */
							if('yes_parent' === $merge_categories)
							{
								$this->log()->debug(__('Category search taking into account nesting.', 'wc1c-main'), ['category' => $cat->getData()]);

								/*
								 * Родитель отсутствует в 1С и в WooCommerce
								 */
								if(false === $cat->hasParent() && empty($group['parent_id']))
								{
									$this->log()->info(__('Category found. The parent is absent both in 1C and on the site.', 'wc1c-main'));

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

								$category = false;
							}
						}
					}

					/**
					 * Слияние разрешено
					 */
					if($category_merge)
					{
						$this->log()->info(__('Assigning a category identifier according to 1C data for an existing category.', 'wc1c-main'));

						// Назначение идентификатора категории
						$category->assignExternalId($group_id);

						// Назначение идентификатора родительской категории
						if(!empty($group['parent_id']))
						{
							$this->log()->info(__('Assigning a parent category ID from 1C to an existing category.', 'wc1c-main'));

							$category->assignExternalParentId($group['parent_id']);
						}

						// Обновление отключено, либо доступно только при совпадении конфигураций
						if('yes' !== $update_categories || 'yes' === $update_categories_only_configuration)
						{
							$this->log()->info(__('Saving a category.', 'wc1c-main'));

							$category->save();
						}
					}
				}

				/**
				 * Категория найдена и включено обновление данных
				 */
				if($category instanceof Category)
				{
					$this->log()->info(__('The category exists. Started updating the data of an existing category.', 'wc1c-main'));

                    if('yes' !== $update_categories)
                    {
                        $this->log()->info(__('Category data update is skipped, it is disabled in the configuration settings.', 'wc1c-main'));
                        continue;
                    }

					/**
					 * Пропуск созданных категорий не под текущей конфигурацией
					 */
					if('yes' === $update_categories_only_configuration && (int)$category->getConfigurationId() !== $this->configuration()->getId())
					{
						$this->log()->notice(__('Category update skipped. The category was created from a different configuration.', 'wc1c-main'));
						continue;
					}

					/**
					 * Обновление имени
					 */
					if('yes' === $update_name)
					{
						$this->log()->info(__('Category name update.', 'wc1c-main'));

						if($category->getName() === $group['name'])
						{
							$this->log()->info(__('The name of the category has not changed, skipping the name update.', 'wc1c-main'));
						}
						else
						{
							$this->log()->info(__('A new category name has been set.', 'wc1c-main'), ['category_old' => $category->getName(), 'category_new' => $group['name']]);

							$category->setName($group['name']);
						}
					}

					/**
					 * Обновление изображения
					 */
					if('yes' === $update_image && isset($images_storage))
					{
						$image_id = 0;

						if(isset($group['image']))
						{
							$file = explode('.', basename($group['image']));

							$image_current = $images_storage->getByExternalName(reset($file));

							if(false === $image_current)
							{
								$this->log()->notice(__('The image updating for the category is missing. It is not found in the media library.', 'wc1c-main'), ['image' => $group['image']]);
							}
							else
							{
								if(is_array($image_current))
								{
									$image_current = reset($image_current);
								}

								$image_id = $image_current->getId();

								if(0 === $image_id)
								{
									$this->log()->notice(__('The image updating for the category is missing. It is not found in the media library.', 'wc1c-main'), ['image' => $group['image']]);
								}
							}
						}

						$category->setImageId($image_id);
					}

					/**
					 * Обновление описания
					 */
					if('yes' === $update_description)
					{
						$this->log()->info(__('Category description update.', 'wc1c-main'));

						if($category->getDescription() === $group['description'])
						{
							$this->log()->info(__('The description of the category has not changed, skipping the description update.', 'wc1c-main'));
						}
						else
						{
							$this->log()->info(__('A new category description has been set.', 'wc1c-main'), ['category_description_old' => $category->getDescription(), 'category_description_new' => $group['description']]);

							$category->setDescription($group['description']);
						}
					}

					/**
					 * Обновление родительской категории
					 */
					if('yes' === $update_parent)
					{
						$this->log()->info(__('Update the parent for the category.', 'wc1c-main'));

						if(empty($group['parent_id']))
						{
							$this->log()->info(__('The parent is absent in 1C.', 'wc1c-main'));

							$category->setParentId(0);
						}
						else
						{
							$this->log()->info(__('Search for a parent category by ID from 1C.', 'wc1c-main'));

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
								$this->log()->info(__('Assigning parent IDs to a category.', 'wc1c-main'));

								$category->setParentId($parent_category->getId());
								$category->assignExternalParentId($group['parent_id']);
							}
						}
					}

                    $category->save();

					$this->log()->info(__('Update data of existing category completed successfully.', 'wc1c-main'));
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
						$this->log()->info(__('Search for a parent category by ID from 1C.', 'wc1c-main'));

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
							$this->log()->info(__('Assigning parent IDs to a category.', 'wc1c-main'));

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
						$this->log()->info(__('Assign a description to a category.', 'wc1c-main'));

						$category->setDescription($group['description']);
					}

					if('yes' === $assign_image && isset($images_storage))
					{
						$this->log()->info(__('Assign a image to a category.', 'wc1c-main'));

						$image_id = 0;

						if(isset($group['image']))
						{
							$file = explode('.', basename($group['image']));

							$image_current = $images_storage->getByExternalName(reset($file));

							if(false === $image_current)
							{
								$this->log()->notice(__('The image assignment for the category is missing. It is not found in the media library.', 'wc1c-main'), ['image' => $group['image']]);
							}
							else
							{
								if(is_array($image_current))
								{
									$image_current = reset($image_current);
								}

								$image_id = $image_current->getId();

								if(0 === $image_id)
								{
									$this->log()->notice(__('The image assignment for the category is missing. It is not found in the media library.', 'wc1c-main'), ['image' => $group['image']]);
								}
							}
						}

						$category->setImageId($image_id);
					}

					$category->save();

					$this->log()->info(__('Category creation completed successfully.', 'wc1c-main'), ['category' => $category->getData()]);
				}
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
		if(!$classifier->hasProperties())
		{
			$this->log()->info(__('Classifier properties is empty.', 'wc1c-main'), ['filetype' => $reader->getFiletype()]);
			return;
		}

		$classifier_properties = $classifier->getProperties();

		$create_attributes = $this->getOptions('attributes_create_by_classifier_properties', 'no');
		$update_attributes_values = $this->getOptions('attributes_values_by_classifier_properties', 'no');

		if
		(
			('yes' === $this->getOptions('attributes_create', 'no') && 'yes' === $create_attributes)
			||
			('yes' === $this->getOptions('attributes_update', 'no') && 'yes' === $update_attributes_values)
		)
		{
			$this->log()->info(__('Creating and updating attributes based on classifier properties.', 'wc1c-main'));

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
						$this->log()->info(__('An existing attribute was found when searching by name.', 'wc1c-main'), ['property_name' => $property['name'], 'attribute' => $attribute->getData()]);
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

						$result_save = $attribute->save();

                        if($result_save === 0)
                        {
                            $this->log()->warning(__('The attribute was not created. Creating error.', 'wc1c-main'));

                            continue;
                        }
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
							$this->log()->info(__('Assigning a value for an attribute.', 'wc1c-main'), ['attribute_name' => $attribute->getName(), 'value' => $values_variant]);

                            $default_term = get_term_by('name', $values_variant, $attribute->getTaxonomyName());

                            if(!$default_term instanceof \WP_Term)
                            {
                                $this->log()->info(__('Value for attribute not found. Adding value.', 'wc1c-main'), ['attribute_name' => $attribute->getName(), 'value' => $values_variant]);

                                if(!$attribute->assignValue($values_variant))
                                {
                                    $this->log()->warning(__('Failed to add value for attribute.', 'wc1c-main'), ['attribute_name' => $attribute->getName(), 'value' => $values_variant]);
                                }
                            }
                            else
                            {
                                $this->log()->info(__('The value for the attribute was added earlier. Skip adding value.', 'wc1c-main'), ['attribute_name' => $attribute->getName(), 'value' => $values_variant]);
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
        $this->log()->info(__('Classifier processing.', 'wc1c-main'));

		$classifier_push = true;
		$all_classifiers = $this->configuration()->getMeta('classifier', false, 'edit');

		if(!empty($all_classifiers) && is_array($all_classifiers))
		{
            $this->log()->info(__('Found existing classifiers. Search in existing ones by ID.', 'wc1c-main'),  ['classifier_id' => $classifier->getId()]);

			$all_classifiers_keys = wp_list_pluck($all_classifiers, 'value');

			foreach($all_classifiers_keys as $key => $value)
			{
				if($value['id'] === $classifier->getId())
				{
					$classifier_push = false;
                    $this->log()->info(__('The processed classifier is found in the existing ones.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);
					break;
				}
			}
		}

		if($classifier_push)
		{
            $this->log()->info(__('Classifier not found. Adding a classifier to existing ones.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

			$this->configuration()->addMetaData
            (
                'classifier',
                [
                    'id' => $classifier->getId(), 'name' => $classifier->getName(), 'filetype' => $reader->getFiletype(),
                    'timestamp' => current_time('timestamp', true)
                ]
            );
		}

		$internal_classifier = $this->configuration()->getMeta('classifier:' . $classifier->getId(), true, 'edit');

		/*
		 * Если классификатор существует, обновляем данные пришедшего из текущего
		 */
		if($internal_classifier instanceof ClassifierDataContract && $classifier->isOnlyChanges())
		{
            $this->log()->info(__('Updating the data of an existing classifier.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

            if($internal_classifier->hasProperties())
			{
                $this->log()->info(__('Update the properties of an existing classifier.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

                $classifier->assignProperties($internal_classifier->getProperties());
			}

			if($internal_classifier->hasGroups())
			{
                $this->log()->info(__('Update the groups of an existing classifier.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

                $classifier->assignGroups($internal_classifier->getGroups());
			}

			if($internal_classifier->hasUnits())
			{
                $this->log()->info(__('Update the units of an existing classifier.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

                $classifier->assignUnits($internal_classifier->getUnits());
			}

			if($internal_classifier->hasWarehouses())
			{
                $this->log()->info(__('Update the warehouses of an existing classifier.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

                $classifier->assignWarehouses($internal_classifier->getWarehouses());
			}

			if($internal_classifier->hasPriceTypes())
			{
                $this->log()->info(__('Update the price types of an existing classifier.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

                $classifier->assignPriceTypes($internal_classifier->getPriceTypes());
			}
		}
        else
        {
            $this->log()->info(__('Saving classifier data in its entirety.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

            $this->configuration()->updateMetaData('classifier:' . $classifier->getId(), $classifier);
        }

		$classifier_groups = $classifier->getGroups();
		if(!empty($classifier_groups))
		{
            $this->log()->info(__('Saving classifier data in terms of groups.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

            $this->configuration()->updateMetaData('classifier-groups:' . $classifier->getId(), $classifier_groups);
		}

		$classifier_categories = $classifier->getCategories();
		if(!empty($classifier_categories))
		{
            $this->log()->info(__('Saving classifier data in terms of categories.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

            $this->configuration()->updateMetaData('classifier-categories:' . $classifier->getId(), $classifier_categories);
		}

		$classifier_properties = $classifier->getProperties();
		if(!empty($classifier_properties))
		{
            $this->log()->info(__('Saving classifier data in terms of properties.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

            $this->configuration()->updateMetaData('classifier-properties:' . $classifier->getId(), $classifier_properties);
		}

        $classifier_prices = $classifier->getPriceTypes();
        if(!empty($classifier_prices))
        {
            $this->log()->info(__('Saving classifier data in terms of price types.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

            $this->configuration()->updateMetaData('classifier-price-types:' . $classifier->getId(), $classifier_prices);
        }

        $classifier_units = $classifier->getUnits();
        if(!empty($classifier_units))
        {
            $this->log()->info(__('Saving classifier data in terms of units.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

            $this->configuration()->updateMetaData('classifier-units:' . $classifier->getId(), $classifier_units);
        }

        $classifier_warehouses = $classifier->getWarehouses();
        if(!empty($classifier_warehouses))
        {
            $this->log()->info(__('Saving classifier data in terms of warehouses.', 'wc1c-main'), ['classifier_id' => $classifier->getId()]);

            $this->configuration()->updateMetaData('classifier-warehouses:' . $classifier->getId(), $classifier_warehouses);
        }

		$this->configuration()->save();
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
		if(false === $reader->isElement())
		{
			if($reader->nodeName === 'Каталог' && $reader->xml_reader->nodeType === XMLReader::END_ELEMENT)
			{
                $products_count = 0;
                if(isset($reader->elements['Товар']))
                {
                    $products_count = $reader->elements['Товар'];
                }

                $this->log()->notice(__('Processing of the product catalog as completed.', 'wc1c-main'), ['products_count' => $products_count]);

                /**
                 * Сохранение каталога в базу данных
                 */
                $this->log()->debug(__('Saving a catalog to meta configuration data.', 'wc1c-main'), ['filetype' => $reader->getFiletype()]);

                // todo: сохранение не только последнего каталога, но и пул всех каталогов?
				$this->configuration()->addMetaData('catalog:' . $reader->catalog->getId(), maybe_serialize($reader->catalog), true);
				$this->configuration()->saveMetaData();
			}

			return;
		}

		if($reader->nodeName === 'Каталог')
		{
			if(is_null($reader->catalog))
			{
				$this->log()->info(__('The catalog object has not been previously initialized. Initialization.', 'wc1c-main'));

				$reader->catalog = new Catalog();
			}

			$only_changes = $reader->xml_reader->getAttribute('СодержитТолькоИзменения') ?: true;
			if($only_changes === 'false')
			{
				$only_changes = false;
			}
			$reader->catalog->setOnlyChanges($only_changes);
		}

		if($reader->parentNodeName === 'Каталог')
		{
			switch($reader->nodeName)
			{
				case 'Ид':
					$reader->catalog->setId($reader->xml_reader->readString());
					$this->log()->debug(__('The catalog object has been assigned an ID.', 'wc1c-main'), ['id' => $reader->catalog->getId()]);
					break;
				case 'ИдКлассификатора':
					$reader->catalog->setClassifierId($reader->xml_reader->readString());
					$this->log()->debug(__('The catalog object has been assigned a classifier ID.', 'wc1c-main'), ['classifier_id' => $reader->catalog->getClassifierId()]);
					break;
				case 'Наименование':
					$reader->catalog->setName($reader->xml_reader->readString());
					$this->log()->debug(__('A name has been assigned to the catalog object.', 'wc1c-main'), ['name' => $reader->catalog->getName()]);
					break;
				case 'Владелец':
					$owner = $reader->decoder()->process('counterparty', $reader->xml_reader->readOuterXml());
					$reader->catalog->setOwner($owner);
					$this->log()->debug(__('The catalog object has been assigned an owner.', 'wc1c-main'), ['owner' => maybe_serialize($owner)]);
					break;
				case 'Описание':
					$reader->catalog->setDescription($reader->xml_reader->readString());
					$this->log()->debug(__('A description has been assigned to the catalog object.', 'wc1c-main'), ['description' => $reader->catalog->getDescription()]);
					break;
				case 'Склады':
					$warehouses = $reader->decoder()->process('warehouses', $reader->xml_reader->readOuterXml());
					$reader->catalog->setWarehouses($warehouses);
                    $this->log()->debug(__('A warehouses has been assigned to the catalog object.', 'wc1c-main'), ['warehouses' => $warehouses]);
					$reader->next();
					break;
			}
		}

        if($reader->nodeName === 'Товары')
        {
            if(false === $reader->catalog->isOnlyChanges())
            {
                $catalog_full_time = current_time('timestamp', true);

                $this->configuration()->addMetaData('_catalog_full_time', $catalog_full_time, true);
                $this->configuration()->saveMetaData();

                $this->log()->notice(__('The catalog contains full data. The time of the last full exchange has been set.', 'wc1c-main'), ['timestamp' => $catalog_full_time, 'catalog_id' => $reader->catalog->getId()]);
            }
        }

		/*
		 * Пропуск создания и обновления продуктов
		 */
		if($reader->nodeName === 'Товары'
            && 'yes' !== $this->getOptions('products_update', 'no')
            && 'yes' !== $this->getOptions('products_create', 'no')
		)
		{
			$this->log()->debug(__('Products creation and updating is disabled. The processing of goods was skipped.', 'wc1c-main'));
			$reader->next();
		}

		if($reader->parentNodeName === 'Товары' && $reader->nodeName === 'Товар')
		{
			$product_xml = new SimpleXMLElement($reader->xml_reader->readOuterXml());

			/**
			 * Декодирование данных продукта из XML в объект реализующий ProductDataContract
			 */
			$product = $reader->decoder->process('product', $product_xml);

			/**
			 * Внешняя фильтрация перед непосредственной обработкой
			 *
			 * @param ProductDataContract $product
			 * @param Reader $reader
			 * @param SchemaAbstract $this
             * @param SimpleXMLElement $product_xml
			 */
			if(has_filter('wc1c_schema_productscml_processing_products'))
			{
				$product = apply_filters('wc1c_schema_productscml_processing_products', $product, $reader, $this, $product_xml);

				$this->log()->debug(__('The product is modified according to external algorithms.', 'wc1c-main'));
			}

			if(!$product instanceof ProductDataContract)
			{
				$this->log()->warning(__('Product !instanceof ProductDataContract. Processing skipped.', 'wc1c-main'), ['data' => $product]);
				return;
			}

			/*
			 * Пропуск продуктов с характеристиками
			 */
			if(true === $product->hasCharacteristicId() && 'yes' !== $this->getOptions('products_with_characteristics', 'no'))
			{
				$this->log()->debug(__('The use of products with characteristics is disabled. Processing skipped.', 'wc1c-main'));
				return;
			}

			try
			{
                /**
                 * Обработка конкретного продукта
                 *
                 * @param ProductDataContract $product
                 * @param Reader $reader
                 * @param SchemaAbstract $this
                 *
                 * @since 0.14
                 * @param SimpleXMLElement $product_xml
                 */
				do_action('wc1c_schema_productscml_processing_products_item', $product, $reader, $this, $product_xml);
			}
			catch(\Throwable $e)
			{
				$this->log()->warning(__('An exception was thrown while saving the product.', 'wc1c-main'), ['exception' => $e]);

                if(has_action('wc1c_schema_productscml_processing_products_item_throwable'))
                {
                    /**
                     * Внешнее действие при выброске исключения
                     *
                     * @param ProductDataContract $product
                     * @param Reader $reader
                     * @param SchemaAbstract $this
                     * @param \Throwable $e
                     *
                     * @since 0.14
                     */
                    do_action('wc1c_schema_productscml_processing_products_item_throwable', $product, $reader, $this, $e);
                }
			}

            $current_product = 0;
            if(isset($reader->elements['Товар']))
            {
                $current_product = $reader->elements['Товар'];
            }

            $this->log()->info(__('Move on to the next product.', 'wc1c-main'), ['current_product_counter' => $current_product]);

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
        $this->log()->info(__('Assign a name to the product.', 'wc1c-main'));

		if($internal_product->isType('variation'))
		{
            $this->log()->debug(__('The product is a variation. Name assignment omitted.', 'wc1c-main'));

			return $internal_product;
		}

		$source = $this->getOptions('products_names_by_cml', 'name');

		if('no' === $source)
		{
            $this->log()->debug(__('The source for assigning the product name has not been selected.', 'wc1c-main'));

            return $internal_product;
		}

		if('update' === $mode && 'yes' !== $this->getOptions('products_update_name', 'no'))
		{
            $this->log()->debug(__('Product name update skipped because its disabled in the settings.', 'wc1c-main'));

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
        $old_name = $internal_product->get_name();

        if($old_name !== $name)
        {
            $internal_product->set_name($name);

            $this->log()->notice(__('Assign name of the product has been successfully completed.', 'wc1c-main'), ['product_id' => $internal_product->getId(), 'name' => $name, 'old_name' => $old_name]);
        }
        else
        {
            $this->log()->info(__('The name assignment for the product is skipping. Name is not changed.', 'wc1c-main'), ['product_id' => $internal_product->getId(), 'name' => $name]);
        }

        return $internal_product;
	}

	/**
	 * Назначение данных по времени
	 *
	 * @param ProductContract $product Экземпляр продукта - либо существующий, либо новый
	 *
	 * @return ProductContract
	 */
	public function setProductTimes(ProductContract $product): ProductContract
	{
		$time = current_time('timestamp', true);

		/**
		 * _wc1c_time
		 * _wc1c_schema_time_{schema_id}
		 * _wc1c_configuration_time_{configuration_id}
		 */
		$product->update_meta_data('_wc1c_time', $time);
		$product->update_meta_data('_wc1c_schema_time_' . $this->getId(), $time);
		$product->update_meta_data('_wc1c_configuration_time_' . $this->configuration()->getId(), $time);

		return $product;
	}

    /**
     * Назначение данных по времени для изображений
     *
     * @param Image $image Экземпляр продукта - либо существующий, либо новый
     *
     * @return Image
     */
    public function setImageTimes(Image $image): Image
    {
        $time = current_time('timestamp', true);

        /**
         * _wc1c_time
         * _wc1c_schema_time_{schema_id}
         * _wc1c_configuration_time_{configuration_id}
         */
        $image->updateMetaData('_wc1c_time', $time);
        $image->updateMetaData('_wc1c_schema_time_' . $this->getId(), $time);
        $image->updateMetaData('_wc1c_configuration_time_' . $this->configuration()->getId(), $time);

        return $image;
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
        $this->log()->info(__('Assign a SKU to a product.', 'wc1c-main'));

        if('update' === $mode && 'no' === $this->getOptions('products_update_sku', 'no'))
		{
            $this->log()->notice(__('SKU update during product update is disabled in the settings.', 'wc1c-main'));

            return $internal_product;
		}

		if('create' === $mode && 'no' === $this->getOptions('products_create_adding_sku', 'yes'))
		{
            $this->log()->notice(__('Adding a SKU when adding a product is disabled in the settings.', 'wc1c-main'));

			return $internal_product;
		}

		$source = $this->getOptions('products_sku_by_cml', 'sku');

		if('no' === $source)
		{
            $this->log()->warning(__('The source for the article is not specified.', 'wc1c-main'));

			return $internal_product;
		}

		$sku = '';
		switch($source)
		{
			case 'code':
				$requisite = 'Код';
				if($external_product->hasRequisites($requisite))
				{
					$requisite_data = $external_product->getRequisites($requisite);
					if(!empty($requisite_data['value']))
					{
						$sku = $requisite_data['value'];
					}
				}
				break;
            case 'barcode':
                if($barcode = $external_product->getBarcode())
                {
                    $sku = $barcode;
                }
                break;
			case 'yes_requisites':
				$requisite = $this->getOptions('products_sku_from_requisites_name', '');
				if($external_product->hasRequisites($requisite))
				{
					$requisite_data = $external_product->getRequisites($requisite);
					if(!empty($requisite_data['value']))
					{
						$sku = $requisite_data['value'];
					}
				}
				break;
			default:
				$sku = $external_product->getSku();
		}

		if('update' === $mode && 'add' === $this->getOptions('products_update_sku', 'no') && !empty($internal_product->getSku()))
		{
            $this->log()->notice(__('SKU update skipped. The mode of adding SKU is enabled for products that do not have them.', 'wc1c-main'));

            return $internal_product;
		}

		if('update' === $mode && empty($sku) && 'yes_yes' === $this->getOptions('products_update_sku', 'no') && empty($internal_product->getSku()))
		{
            $this->log()->notice(__('SKU update skipped. The mode for adding SKUs is enabled for products that have them on the site and in 1C.', 'wc1c-main'));

            return $internal_product;
		}

		try
		{
			$internal_product->setSku($sku);

            $this->log()->info(__('The SKU assignment for the product has been successfully completed.', 'wc1c-main'), ['product_id' => $internal_product->getId(), 'sku' => $external_product->getSku()]);
		}
		catch(\Throwable $e)
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

		$visible = $this->getOptions('products_update_set_catalog_visibility', '');

		if(!empty($visible))
		{
			$internal_product->set_catalog_visibility($visible);
		}

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

		if('no' === $this->getOptions('products_update_set_reviews_allowed', 'no'))
		{
			return $internal_product;
		}

		if('yes' === $this->getOptions('products_update_set_reviews_allowed', 'no'))
		{
			$internal_product->set_reviews_allowed(true);
		}
		else
		{
			$internal_product->set_reviews_allowed(false);
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
			$internal_product->set_sold_individually(false);
			if('yes' === $this->getOptions('products_create_set_sold_individually', 'no'))
			{
				$internal_product->set_sold_individually(true);
			}

			return $internal_product;
		}

		if('no' === $this->getOptions('products_update_set_sold_individually', 'no'))
		{
			return $internal_product;
		}

		if('yes' === $this->getOptions('products_update_set_sold_individually', 'no'))
		{
			$internal_product->set_sold_individually(true);
		}
		else
		{
			$internal_product->set_sold_individually(false);
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
			$internal_product->set_featured(false);
			if('yes' === $this->getOptions('products_create_set_featured', 'no'))
			{
				$internal_product->set_featured(true);
			}

			return $internal_product;
		}

		if('no' === $this->getOptions('products_update_set_featured', 'no'))
		{
			return $internal_product;
		}

		if('yes' === $this->getOptions('products_update_set_featured', 'no'))
		{
			$internal_product->set_featured(true);
		}
		else
		{
			$internal_product->set_featured(false);
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
        $this->log()->info(__('Assign a description to the product.', 'wc1c-main'));

        if('create' === $mode && 'no' === $this->getOptions('products_create_adding_description', 'yes'))
		{
            $this->log()->debug(__('Assigning a description to the created product is disabled. Assigning description skipped.', 'wc1c-main'));

			return $internal_product;
		}

		if('update' === $mode && 'no' === $this->getOptions('products_update_description', 'no'))
		{
            $this->log()->debug(__('Assigning a description to the updated product is disabled. Assigning description skipped.', 'wc1c-main'));

            return $internal_product;
		}

		$short_description = '';

		$short = $this->getOptions('products_descriptions_short_by_cml', 'yes');

		if('no' !== $short)
		{
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
				case 'yes_specification':
					$data = $external_product->getData();
					$short_description = $data['specification'] ?? '';
					break;
				default:
					$short_description = $external_product->getDescription();
			}
		}

		if('update' === $mode && 'add' === $this->getOptions('products_update_description', 'yes') && !empty($internal_product->get_short_description()))
		{
            $this->log()->debug(__('When updating products, it is allowed to add descriptions only to products without a description on the site if there is a description in 1C. Assigning skipped.', 'wc1c-main'));

            return $internal_product;
		}

		if('update' === $mode && empty($short_description) && 'yes_yes' === $this->getOptions('products_update_description', 'yes') && empty($internal_product->get_short_description()))
		{
            $this->log()->debug(__('When updating products, it is allowed to add descriptions only to products with a description on the site if there is a description in 1C. Assigning skipped.', 'wc1c-main'));

            return $internal_product;
		}

        $old_short_description = $internal_product->get_short_description();

        if(md5($old_short_description) !== md5($short_description))
        {
            $internal_product->set_short_description($short_description);

            $this->log()->notice(__('Assign description of the product has been successfully completed.', 'wc1c-main'), ['product_id' => $internal_product->getId(), 'description' => $short_description, 'old_description' => $old_short_description]);
        }
        else
        {
            $this->log()->info(__('The description assignment for the product is skipping. Description is not changed.', 'wc1c-main'), ['product_id' => $internal_product->getId()]);
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
        $this->log()->info(__('Assign a full description to the product.', 'wc1c-main'));

		if('create' === $mode && 'no' === $this->getOptions('products_create_adding_description_full', 'yes'))
		{
            $this->log()->debug(__('Assigning a full description to the created product is disabled. Assigning description skipped.', 'wc1c-main'));

            return $internal_product;
		}

		if('update' === $mode && 'no' === $this->getOptions('products_update_description_full', 'no'))
		{
            $this->log()->debug(__('Assigning a full description to the updated product is disabled. Assigning description skipped.', 'wc1c-main'));

            return $internal_product;
		}

		$full_description = '';
		$full = $this->getOptions('products_descriptions_by_cml', 'yes');

		if('no' !== $full)
		{
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
				case 'yes_specification':
					$data = $external_product->getData();
					$full_description = $data['specification'] ?? '';
					break;
				default:
					$full_description = $external_product->getDescription();
			}
		}

		if('update' === $mode && 'add' === $this->getOptions('products_update_description_full', 'yes') && !empty($internal_product->get_description()))
		{
            $this->log()->debug(__('When updating products, it is allowed to add full descriptions only to products without a description on the site if there is a description in 1C. Assigning skipped.', 'wc1c-main'));

            return $internal_product;
		}

		if('update' === $mode && empty($full_description) && 'yes_yes' === $this->getOptions('products_update_description_full', 'yes') && empty($internal_product->get_description()))
		{
            $this->log()->debug(__('When updating products, it is allowed to add full descriptions only to products with a description on the site if there is a description in 1C. Assigning skipped.', 'wc1c-main'));

            return $internal_product;
		}

        $old_full_description = $internal_product->get_description();

        if(md5($old_full_description) !== md5($full_description))
        {
            $internal_product->set_description($full_description);

            $this->log()->notice(__('Assign full description of the product has been successfully completed.', 'wc1c-main'), ['product_id' => $internal_product->getId(), 'description' => $full_description, 'old_description' => $old_full_description]);
        }
        else
        {
            $this->log()->info(__('The full description assignment for the product is skipping. Description is not changed.', 'wc1c-main'), ['product_id' => $internal_product->getId()]);
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
        $this->log()->info(__('Assign categories to a product.', 'wc1c-main'));

        if('create' === $mode && 'no' === $this->getOptions('products_create_adding_category', 'yes'))
		{
            $this->log()->notice(__('Assigning categories when creating products is disabled in the settings.', 'wc1c-main'));

            return $internal_product;
		}

        if('create' === $mode && false === $external_product->hasClassifierGroups())
        {
            $this->log()->notice(__('The assignment of categories when creating products is enabled, but in 1C the product does not have groups.', 'wc1c-main'));

            return $internal_product;
        }

		if('update' === $mode && 'no' === $this->getOptions('products_update_categories', 'no'))
		{
            $this->log()->notice(__('Update categories when updating products is disabled in the settings.', 'wc1c-main'));

			return $internal_product;
		}

		if($internal_product->isType('variation'))
		{
            $this->log()->info(__('Variations cannot be categorized. Skip assigning categories.', 'wc1c-main'));

			return $internal_product;
		}

        $source = $this->getOptions('products_categories_source', 'classifier_groups');

        if('no' === $source)
        {
            $this->log()->notice(__('The source for assigning categories was not specified. Skip assigning categories.', 'wc1c-main'));

            return $internal_product;
        }

        $cats = [];

        if(($source === 'classifier_groups') && $external_product->hasClassifierGroups())
        {
            /** @var CategoriesStorageContract $categories_storage */
            $categories_storage = Storage::load('category');

            $classifier_groups = $external_product->getClassifierGroups();

            $this->log()->info(__('Filling in product categories based on classifier groups.', 'wc1c-main'));

            foreach($classifier_groups as $classifier_group)
            {
                $this->log()->debug(__('Processing of the classifier group.', 'wc1c-main'), ['group' => $classifier_group]);

                $cat = $categories_storage->getByExternalId($classifier_group);

                if($cat instanceof Category)
                {
                    $this->log()->debug(__('The category was found by the external group ID from 1C.', 'wc1c-main'), ['category_id' => $cat->getId()]);

                    $cats[] = $cat->getId();
                    continue;
                }

                $this->log()->warning(__('Category not found by external group ID from 1C.', 'wc1c-main'), ['group' => $classifier_group]);
            }
        }

        if('update' === $mode && 'add' === $this->getOptions('products_update_categories', 'no') && !empty($internal_product->get_category_ids()))
        {
            $this->log()->notice(__('Categories update skipped. The mode of adding categories is enabled for products that do not have them.', 'wc1c-main'));

            return $internal_product;
        }

        if('update' === $mode && empty($cats) && 'yes_yes' === $this->getOptions('products_update_categories', 'no') && empty($internal_product->get_category_ids()))
        {
            $this->log()->notice(__('Categories update skipped. The mode for adding categories is enabled for products that have them on the site and in 1C.', 'wc1c-main'));

            return $internal_product;
        }

        if
        (
            ('create' === $mode && $this->getOptions('products_create_adding_category_fill_parent', 'yes') === 'yes')
            ||
            ('update' === $mode && $this->getOptions('products_update_categories_fill_parent', 'yes') === 'yes')
        )
        {
            $this->log()->info(__('Filling parent categories according to the WordPress structure.', 'wc1c-main'));

            $this->fillParentCategories($cats);

            $this->log()->debug(__('Filling result.', 'wc1c-main'), ['categories' => $cats]);
        }

		$internal_product->set_category_ids($cats);

        $this->log()->info(__('Category assignment completed successfully.', 'wc1c-main'), ['categories' => $cats, 'product_id' => $internal_product->getId()]);

        return $internal_product;
	}

    /**
     * Заполняет родительские категории у продукта
     *
     * @param $product_categories
     *
     * @return array|mixed
     */
    private function fillParentCategories(&$product_categories)
    {
        foreach($product_categories as $category_id)
        {
            $parents = $this->findParentCategories($category_id);

            foreach($parents as $parent_id)
            {
                $key = array_search($parent_id, $product_categories, true);

                if($key === false)
                {
                    $product_categories[] = $parent_id;
                }
            }
        }

        return $product_categories;
    }

    /**
     * Поиск всех родительских категорий
     *
     * @param int $category_id
     *
     * @return array
     */
    private function findParentCategories(int $category_id): array
    {
        return get_ancestors($category_id, 'product_cat');
    }

	/**
	 * Назначение данных продукта исходя из режима: статус налога
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 * @throws Exception
	 */
	public function assignProductsItemTaxesStatus(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if($internal_product->isType('variation')) // todo: назначение налогов для вариации
		{
			return $internal_product;
		}

		if('create' === $mode)
		{
			$internal_product->set_tax_status($this->getOptions('products_create_taxes_status', 'taxable'));

			return $internal_product;
		}

		$status = $this->getOptions('products_update_taxes_status', 'no');

		if('no' === $status)
		{
			return $internal_product;
		}

		$internal_product->set_tax_status($status);

		return $internal_product;
	}

	/**
	 * Назначение данных продукта исходя из режима: класс налога
	 *
	 * @param ProductContract $internal_product Экземпляр продукта - либо существующий, либо новый
	 * @param ProductDataContract $external_product Данные продукта из XML
	 * @param string $mode Режим - create или update
	 * @param Reader $reader Текущий итератор
	 *
	 * @return ProductContract
	 * @throws Exception
	 */
	public function assignProductsItemTaxesClass(ProductContract $internal_product, ProductDataContract $external_product, string $mode, Reader $reader): ProductContract
	{
		if($internal_product->isType('variation')) // todo: назначение налогов для вариации
		{
			return $internal_product;
		}

		if('create' === $mode)
		{
			$internal_product->set_tax_class($this->getOptions('products_create_taxes_class', 'standard'));

			return $internal_product;
		}

		$class = $this->getOptions('products_update_taxes_class', 'no');

		if('no' === $class)
		{
			return $internal_product;
		}

		$internal_product->set_tax_class($class);

		return $internal_product;
	}

    /**
     * Назначение данных продукта на основе пакета предложений: изображения
     *
     * @param ProductContract $internal_offer Экземпляр обновляемого продукта
     * @param ProductDataContract $external_offer Данные продукта в CML
     * @param Reader $reader Текущий итератор
     *
     * @return ProductContract
     * @throws Exception
     */
    public function assignOffersItemImages(ProductContract $internal_offer, ProductDataContract $external_offer, Reader $reader): ProductContract
    {
        $this->log()->info(__('Assigning images to a product by offers.', 'wc1c-main'));

        if(false === $internal_offer->isType('variation'))
        {
            $this->log()->debug(__('Assigning of images based on offer package is only possible for variations. Assignment skipped.', 'wc1c-main'));

            return $internal_offer;
        }

        if('yes' !== $this->getOptions('products_images_by_cml', 'no'))
        {
            $this->log()->debug(__('Image assignments for CommerceML data are disabled in the settings. Assignment skipped.', 'wc1c-main'));

            return $internal_offer;
        }

        if(false === $external_offer->hasImages())
        {
            $this->log()->debug(__('There are no images for the product. Assignment skipped.', 'wc1c-main'));

            return $internal_offer;
        }

        if('no' === $this->getOptions('products_update_images', 'no'))
        {
            $this->log()->debug(__('Image assigning for the product being updated is disabled. Assignment skipped.', 'wc1c-main'));

            return $internal_offer;
        }

        $images_mode = $this->getOptions('products_update_images', 'no');
        $images_max = $this->getOptions('products_images_by_cml_max', 10);
        $external_images = $external_offer->getImages();

        if('add' === $images_mode && !empty($internal_offer->get_image_id()))
        {
            $this->log()->debug(__('The product being updated contains images. Adding images is allowed only if there are none. Assignment skipped.', 'wc1c-main'));

            return $internal_offer;
        }

        if(empty($external_images) && 'yes_yes' === $images_mode && empty($internal_offer->get_image_id()))
        {
            $this->log()->debug(__('The product being updated does not contain an image. Updating images is allowed only if they are present on the site and in 1C. Assignment skipped.', 'wc1c-main'));

            return $internal_offer;
        }

        /** @var ImagesStorageContract $images_storage */
        $images_storage = Storage::load('image');

        $product_factory = new Factory();
        $parent_offer = $product_factory->getProduct($internal_offer->get_parent_id());

        $gallery_image_ids = [];

        if(is_array($external_images))
        {
            foreach($external_images as $index => $image)
            {
                if($index >= $images_max)
                {
                    $this->log()->debug(__('The maximum possible number of images has been processed. The rest of the images are skip.', 'wc1c-main'));
                    break;
                }

                $file = explode('.', basename($image));

                $image_current = $images_storage->getByExternalName(reset($file));

                if(false === $image_current)
                {
                    $this->log()->warning(__('The image assignment for the product is missing. Image is not found in the media library.', 'wc1c-main'), ['image' => $image]);
                    continue;
                }

                if(is_array($image_current))
                {
                    $image_current = reset($image_current);
                }

                $attach_id = $image_current->getId();

                if(0 === $attach_id)
                {
                    $this->log()->warning(__('The image assignment for the product is missing. Image is not found in the media library.', 'wc1c-main'), ['image' => $image]);
                    continue;
                }

                $image_current->setProductId($internal_offer->getId());
                $image_current->save();

                if($index === 0)
                {
                    $internal_offer->set_image_id($attach_id);

                    if(empty($parent_offer->get_image_id()))
                    {
                        $parent_offer->set_image_id($attach_id);
                    }
                    else
                    {
                        $gallery_image_ids[] = $attach_id;
                    }

                    $this->log()->debug(__('Assigning a main image for a product variation.', 'wc1c-main'), ['image_id' => $attach_id]);
                }
            }
        }

        $parent_offer->set_gallery_image_ids($gallery_image_ids);
        $parent_offer->save();

        $this->log()->info(__('Assigning images to a product by offers as completed.', 'wc1c-main'), ['images' => $gallery_image_ids]);

        return $internal_offer;
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
        $this->log()->info(__('Assign images to a product.', 'wc1c-main'));

        if('yes' !== $this->getOptions('products_images_by_cml', 'no'))
		{
            $this->log()->debug(__('Image assignments for CommerceML data are disabled in the settings. Assigning skipped.', 'wc1c-main'));

			return $internal_product;
		}

		if('create' === $mode && false === $external_product->hasImages())
		{
            $this->log()->debug(__('There are no images for the product being created. Assigning skipped.', 'wc1c-main'));

			return $internal_product;
		}

		if($internal_product->isType('variation')) // todo: назначение одного изображения для вариации по данным каталога?
		{
            $this->log()->debug(__('Assigning images to a product variation is not possible. Assigning skipped', 'wc1c-main'));

			return $internal_product;
		}

		if('create' === $mode && 'yes' !== $this->getOptions('products_create_adding_images', 'no'))
		{
            $this->log()->debug(__('Assigning images to the created product is disabled. Assigning skipped.', 'wc1c-main'));

			return $internal_product;
		}

		if('update' === $mode && 'no' === $this->getOptions('products_update_images', 'no'))
		{
            $this->log()->debug(__('Image assignment for the product being updated is disabled. Assigning skipped.', 'wc1c-main'));

			return $internal_product;
		}

		$images_mode = $this->getOptions('products_update_images', 'no');
		$images_max = $this->getOptions('products_images_by_cml_max', 10);
		$external_images = $external_product->getImages();

		if('update' === $mode && 'add' === $images_mode && !empty($internal_product->get_image_id()))
		{
            $this->log()->debug(__('The product being updated contains images. Adding images is allowed only if there are none. Assigning skipped.', 'wc1c-main'));

			return $internal_product;
		}

		if('update' === $mode && empty($external_images) && 'yes_yes' === $images_mode && empty($internal_product->get_image_id()))
		{
            $this->log()->debug(__('The product being updated does not contain an image. Updating images is allowed only if they are present on the site and in 1C. Assigning skipped.', 'wc1c-main'));

            return $internal_product;
		}

		/** @var ImagesStorageContract $images_storage */
		$images_storage = Storage::load('image');

		$gallery_image_ids = [];

		if(is_array($external_images))
		{
			foreach($external_images as $index => $image)
			{
				if($index >= $images_max)
				{
					$this->log()->debug(__('The maximum possible number of images has been processed. The rest of the images are skip.', 'wc1c-main'));
					break;
				}

				$file = explode('.', basename($image));

				$image_current = $images_storage->getByExternalName(reset($file));

				if(false === $image_current)
				{
					$this->log()->warning(__('The image assignment for the product is missing. Image is not found in the media library.', 'wc1c-main'), ['image' => $image]);
					continue;
				}

				if(is_array($image_current))
				{
					$image_current = reset($image_current);
				}

				$attach_id = $image_current->getId();

				if(0 === $attach_id)
				{
					$this->log()->warning(__('The image assignment for the product is missing. Image is not found in the media library.', 'wc1c-main'), ['image' => $image]);
					continue;
				}

				$image_current->setProductId($internal_product->getId());
				$image_current->save();

				if($index === 0)
				{
					$internal_product->set_image_id($attach_id);

                    $this->log()->debug(__('Assigning a main image for a product.', 'wc1c-main'), ['image_id' => $attach_id]);

                    continue;
				}

				$gallery_image_ids[] = $attach_id;
			}
		}

		$internal_product->set_gallery_image_ids($gallery_image_ids);

        $this->log()->info(__('Assign images to a product as completed.', 'wc1c-main'), ['images' => $gallery_image_ids]);

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
		if('create' === $mode && 'no' === $this->getOptions('products_create_adding_dimensions', 'yes'))
		{
			return $internal_product;
		}

		if('update' === $mode && 'no' === $this->getOptions('products_update_dimensions', 'no'))
		{
			return $internal_product;
		}

		$dimensions_source =  $this->getOptions('products_dimensions_source', 'yes_requisites');

		if($dimensions_source === 'no')
		{
			return $internal_product;
		}

		/**
		 * Вес
		 */
		$weight = '';
		$weight_name = trim($this->getOptions('products_dimensions_by_requisites_weight_from_name', 'Вес'));

		if($weight_name !== '' && $dimensions_source === 'yes_requisites' && $external_product->hasRequisites($weight_name))
		{
			$requisite_data = $external_product->getRequisites($weight_name);
			if(!empty($requisite_data['value']))
			{
				$weight = $requisite_data['value'];
			}
		}

		if(has_filter('wc1c_schema_productscml_products_dimensions_weight'))
		{
			$weight = apply_filters('wc1c_schema_productscml_products_dimensions_weight', $weight, $internal_product, $external_product, $mode, $reader, $this);
		}

		if('update' === $mode && 'add' === $this->getOptions('products_update_dimensions', 'no') && empty($internal_product->get_weight()))
		{
			$internal_product->set_weight($weight);
		}

		if('update' === $mode && !empty($weight) && 'yes_yes' === $this->getOptions('products_update_dimensions', 'no') && !empty($internal_product->get_weight()))
		{
			$internal_product->set_weight($weight);
		}

		if('update' === $mode && 'yes' === $this->getOptions('products_update_dimensions', 'no'))
		{
			$internal_product->set_weight($weight);
		}

		if('create' === $mode)
		{
			$internal_product->set_weight($weight);
		}

		/**
		 * Длина
		 */
		$length = '';
		$length_name = trim($this->getOptions('products_dimensions_by_requisites_length_from_name', 'Длина'));

		if($length_name !== '' && $dimensions_source === 'yes_requisites' && $external_product->hasRequisites($length_name))
		{
			$requisite_data = $external_product->getRequisites($length_name);
			if(!empty($requisite_data['value']))
			{
				$length = $requisite_data['value'];
			}
		}

		if(has_filter('wc1c_schema_productscml_products_dimensions_length'))
		{
			$length = apply_filters('wc1c_schema_productscml_products_dimensions_length', $length, $internal_product, $external_product, $mode, $reader, $this);
		}

		if('update' === $mode && 'add' === $this->getOptions('products_update_dimensions', 'no') && empty($internal_product->get_length()))
		{
			$internal_product->set_length($length);
		}

		if('update' === $mode && !empty($length) && 'yes_yes' === $this->getOptions('products_update_dimensions', 'no') && !empty($internal_product->get_length()))
		{
			$internal_product->set_length($length);
		}

		if('update' === $mode && 'yes' === $this->getOptions('products_update_dimensions', 'no'))
		{
			$internal_product->set_length($length);
		}

		if('create' === $mode)
		{
			$internal_product->set_length($length);
		}

		/**
		 * Ширина
		 */
		$width = '';
		$width_name = trim($this->getOptions('products_dimensions_by_requisites_width_from_name', 'Ширина'));

		if($width_name !== '' && $dimensions_source === 'yes_requisites' && $external_product->hasRequisites($width_name))
		{
			$requisite_data = $external_product->getRequisites($width_name);
			if(!empty($requisite_data['value']))
			{
				$width = $requisite_data['value'];
			}
		}

		if(has_filter('wc1c_schema_productscml_products_dimensions_width'))
		{
			$width = apply_filters('wc1c_schema_productscml_products_dimensions_width', $width, $internal_product, $external_product, $mode, $reader, $this);
		}

		if('update' === $mode && 'add' === $this->getOptions('products_update_dimensions', 'no') && empty($internal_product->get_width()))
		{
			$internal_product->set_width($width);
		}

		if('update' === $mode && !empty($width) && 'yes_yes' === $this->getOptions('products_update_dimensions', 'no') && !empty($internal_product->get_width()))
		{
			$internal_product->set_width($width);
		}

		if('update' === $mode && 'yes' === $this->getOptions('products_update_dimensions', 'no'))
		{
			$internal_product->set_width($width);
		}

		if('create' === $mode)
		{
			$internal_product->set_width($width);
		}

		/**
		 * Высота
		 */
		$height = '';
		$height_name = trim($this->getOptions('products_dimensions_by_requisites_height_from_name', 'Высота'));

		if($height_name !== '' && $dimensions_source === 'yes_requisites' && $external_product->hasRequisites($height_name))
		{
			$requisite_data = $external_product->getRequisites($height_name);
			if(!empty($requisite_data['value']))
			{
				$height = $requisite_data['value'];
			}
		}

		if(has_filter('wc1c_schema_productscml_products_dimensions_height'))
		{
			$height = apply_filters('wc1c_schema_productscml_products_dimensions_height', $height, $internal_product, $external_product, $mode, $reader, $this);
		}

		if('update' === $mode && 'add' === $this->getOptions('products_update_dimensions', 'no') && empty($internal_product->get_height()))
		{
			$internal_product->set_height($height);
		}

		if('update' === $mode && !empty($height) && 'yes_yes' === $this->getOptions('products_update_dimensions', 'no') && !empty($internal_product->get_height()))
		{
			$internal_product->set_height($height);
		}

		if('update' === $mode && 'yes' === $this->getOptions('products_update_dimensions', 'no'))
		{
			$internal_product->set_height($height);
		}

		if('create' === $mode)
		{
			$internal_product->set_height($height);
		}

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

        // Stop if parent is variation.
		if($parent->isType('variation'))
		{
			$this->log()->warning(__('The parent product is variation. Skipped.', 'wc1c-main'), ['parent_id' => $variation->get_parent_id()]);
			return $variation;
		}

		if(!empty($raw_attributes))
		{
			$attributes = [];
			$parent_attributes = $this->getVariationParentAttributes($raw_attributes, $parent);

            $this->log()->debug(__('The parent product attributes.', 'wc1c-main'), ['parent_attributes' => $parent_attributes]);

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

				if(!isset($parent_attributes[$attribute_name]))
				{
                    $this->log()->debug(__('The attribute was not found in the parent product.', 'wc1c-main'), ['attribute_name' => $attribute_name]);
                    continue;
				}

                if(!$parent_attributes[$attribute_name]->get_variation())
                {
                    $this->log()->debug(__('The attribute is not for variations.', 'wc1c-main'), ['attribute_name' => $attribute_name]);
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
        $this->log()->debug(__('Getting to variation parent attributes.', 'wc1c-main'), ['attributes' => $attributes]);

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
                $this->log()->notice(__('The attribute is not for variations. Save required.', 'wc1c-main'), ['attribute_name' => $attribute_name]);

				// Re-create the attribute to CRUD save and generate again.
				$parent_attributes[$attribute_name] = clone $parent_attributes[$attribute_name];
				$parent_attributes[$attribute_name]->set_variation(1);

				$require_save = true;
			}
		}

		// Save parent attributes.
		if($require_save)
		{
            $this->log()->info(__('Preserve parent attributes for accessibility in variations.', 'wc1c-main'), ['attributes' => $parent_attributes]);

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
        $this->log()->info(__('Processing of product attributes.', 'wc1c-main'));

		if('create' === $mode && 'yes' !== $this->getOptions('products_create_adding_attributes', 'yes'))
		{
            $this->log()->notice(__('Assigning attributes when creating products is disabled. Attribute assignment skipped.', 'wc1c-main'));

			return $internal_product;
		}

		if('update' === $mode && 'yes' !== $this->getOptions('products_update_attributes', 'no'))
		{
            $this->log()->notice(__('Assigning attributes when updating products is disabled. Attribute assignment skipped.', 'wc1c-main'));

            return $internal_product;
		}

		$this->log()->info(__('Assigning attributes to a product based on the properties of the product catalog.', 'wc1c-main'), ['mode' => $mode, 'filetype' => $reader->getFiletype(), 'internal_product_id' => $internal_product->getId(), 'external_product_id' => $external_product->getId()]);

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

			$classifier_properties = maybe_unserialize($this->configuration()->getMeta('classifier-properties:' . $reader->catalog->getClassifierId()));

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
					$this->log()->notice(__('The characteristic has an empty value.', 'wc1c-main'), ['characteristic_id' => $characteristic_id, 'value' => $characteristic_value]);
					continue;
				}

				$old_characteristics[$characteristic_id] = $characteristic_value;

				$global = $attributes_storage->getByLabel($characteristic_value['name']);

                if(false === $global && 'yes' === $this->getOptions('attributes_create_by_product_characteristics', 'yes'))
                {
                    $this->log()->info(__('The attribute was not found. Creating by characteristic.', 'wc1c-main'));

                    $attribute = new Attribute();
                    $attribute->setLabel($characteristic_value['name']);

                    $attribute->save();

                    $global = $attributes_storage->getByLabel($characteristic_value['name']);
                }

				$attribute_name = $global ? $global->getName() : $characteristic_value['name'];

				$value = $raw_attributes[$attribute_name]['value'] ?? [];

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
				$raw_attributes[$attribute_name] =
				[
					'name' => $attribute_name,
					'value' => $value,
					'visible' => 1,
					'variation' => 1,
					'taxonomy' => $global ? 1 : 0,
				];
			}

			if(!empty($external_product->getCharacteristicId()) && isset($parent_characteristics) && $parent_characteristics instanceof VariableProduct)
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
        $this->log()->info(__('Assigning attributes to a product based on the properties of the offers package.', 'wc1c-main'), ['filetype' => $reader->getFiletype(), 'internal_product_id' => $internal_product->getId()]);

        if($reader->getFiletype() !== 'offers' && $reader->schema_version !== '3.1')
		{
            $this->log()->notice(__('The file type is not an offer package. Skip assigning attributes on offer data.', 'wc1c-main'));

            return $internal_product;
		}

		/** @var AttributesStorageContract $attributes_storage */
		$attributes_storage = Storage::load('attribute');

		$raw_attributes = [];

		$parent_product = false;
		if($internal_product->get_parent_id() !== 0)
		{
			$parent_product = (new Factory())->getProduct($internal_product->get_parent_id()); // todo: cache
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

			$classifier_properties = maybe_unserialize($this->configuration()->getMeta('classifier-properties:' . $reader->offers_package->getClassifierId()));

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

			if($parent_product instanceof VariableProduct)
			{
				$old_characteristics = maybe_unserialize($parent_product->get_meta('_wc1c_characteristics', true));
				if(empty($old_characteristics))
				{
					$old_characteristics = [];
				}
			}

			foreach($external_product->getCharacteristics() as $characteristic_id => $characteristic_value)
			{
                $variation_meta_name = 'attribute_';
                $variation_meta_value = '';

				if(empty($characteristic_value['value']))
				{
					$this->log()->info(__('The characteristic has an empty value.', 'wc1c-main'), ['characteristic_id' => $characteristic_id, 'value' => $characteristic_value]);
					continue;
				}

				$old_characteristics[] = $characteristic_value;

				$global = $attributes_storage->getByLabel($characteristic_value['name']);

                if(false === $global && 'yes' === $this->getOptions('attributes_create_by_product_characteristics', 'yes'))
                {
                    $this->log()->info(__('The attribute was not found. Creating by characteristic.', 'wc1c-main'));

                    $attribute = new Attribute();
                    $attribute->setLabel($characteristic_value['name']);

                    $attribute->save();

                    $global = $attributes_storage->getByLabel($characteristic_value['name']);
                }

				$attribute_name = $global ? $global->getName() : $characteristic_value['name'];

				$value = $raw_attributes[$attribute_name]['value'] ?? [];

                if($global)
                {
                    $variation_meta_name .= 'pa_' . \esc_attr(\sanitize_title($global->getName()));
                    $variation_term = get_term_by('name', $characteristic_value['value'], $global->getTaxonomyName());
                    $variation_meta_value = $variation_term->slug;
                }

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
				$raw_attributes[$attribute_name] =
				[
					'name' => $attribute_name,
					'value' => $value,
					'visible' => 1,
					'variation' => 1,
					'taxonomy' => $global ? 1 : 0,
				];

                $internal_product->update_meta_data($variation_meta_name, $variation_meta_value);
			}

			if($parent_product instanceof VariableProduct)
			{
				$import_characteristics = maybe_unserialize($parent_product->get_meta('_wc1c_properties_import', true));
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

                    if(false === $global && 'yes' === $this->getOptions('attributes_create_by_product_characteristics', 'yes'))
                    {
                        $this->log()->info(__('The attribute was not found. Creating by characteristic.', 'wc1c-main'));

                        $attribute = new Attribute();
                        $attribute->setLabel($characteristic_value['name']);

                        $attribute->save();

                        $global = $attributes_storage->getByLabel($characteristic_value['name']);
                    }

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

				$this->setProductAttributes($parent_product, $parent_attr);

				$parent_product->update_meta_data('_wc1c_characteristics', $old_characteristics);
				$parent_product->save();
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
		if($reader->schema_version === '3.1' && $reader->getFiletype() !== 'prices')
		{
			return $internal_product;
		}

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
							$regular_price_id = $price_type['id'];
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
							$sale_price_id = $price_type['id'];
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
		if($reader->schema_version === '3.1' && $reader->getFiletype() !== 'rests')
		{
			return $internal_product;
		}

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

		if($internal_product->get_stock_status() !== 'instock'
		   && $internal_product->get_stock_status() !== 'outofstock'
		   && $internal_product->get_stock_status() !==  'onbackorder')
		{
			return $internal_product;
		}

		$internal_product->set_manage_stock(true);
		if('yes' !== get_option('woocommerce_manage_stock'))
		{
			$internal_product->set_manage_stock(false);
		}

		$product_quantity = $external_product->getQuantity();

		if($product_quantity < $this->getOptions('products_inventories_quantities_min', 1))
		{
			$product_quantity = 0;
		}

		$stock_status = $product_quantity > 0 ? 'instock' : 'outofstock';

		if($internal_product->managing_stock())
		{
			wc_update_product_stock($internal_product, $product_quantity, 'set');
		}

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
		$this->log()->info(__('Processing a product from a catalog of products.', 'wc1c-main'), ['catalog_id' => $reader->catalog->getId(), 'product_id' => $external_product->getId(), 'product_characteristic_id' => $external_product->getCharacteristicId()]);

		$product_id = 0;
		$product_factory = new Factory();

		/*
		 * Поиск продукта по идентификатору 1С
		 */
		if('yes' === $this->getOptions('product_sync_by_id', 'yes'))
		{
            $this->log()->info(__('Product search by external ID from 1C.', 'wc1c-main'), ['product_id' => $product_id]);

            $product_id = $product_factory->findIdsByExternalIdAndCharacteristicId($external_product->getId(), $external_product->getCharacteristicId());

			if(is_array($product_id)) // todo: обработка нескольких?
			{
				$this->log()->warning(__('Several identical products were found. The first one is selected.', 'wc1c-main'), ['product_ids' => $product_id]);
				$product_id = reset($product_id);
            }

            $this->log()->debug(__('Product search result by external ID from 1C.', 'wc1c-main'), ['product_id' => $product_id]);
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
            $this->log()->info(__('Product search by external algorithms.', 'wc1c-main'), ['product_id' => $product_id]);

            $product_id = apply_filters('wc1c_schema_productscml_processing_products_search', $product_id, $external_product, $this, $reader);

            if(is_array($product_id)) // todo: обработка нескольких?
            {
                $this->log()->warning(__('Several identical products were found. The first one is selected.', 'wc1c-main'), ['product_ids' => $product_id]);
                $product_id = reset($product_id);
            }

            $this->log()->debug(__('Product search result by external algorithms.', 'wc1c-main'), ['product_id' => $product_id]);
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
				$this->log()->debug(__('Products create is disabled. Product create skipped.', 'wc1c-main'));
				return;
			}

			/*
			 * Пропуск создания продуктов помеченных к удалению в 1С
			 */
			if($external_product->hasDeleted() && 'yes' !== $this->getOptions('products_create_delete_mark', 'no'))
			{
				$this->log()->info(__('The use of products delete mark is disabled. Product create skipped.', 'wc1c-main'));
				return;
			}

            /**
             * Продукт с характеристикой
             * ---
             * Проверяем наличие родительского продукта для продукта с характеристикой, и
             * если родительского продукта нет, пропускаем обработку.
             * Исключение составляет включенная возможность создания родительского продукта на основе первой характеристики.
             */
			if($external_product->hasCharacteristicId())
			{
				$this->log()->info(__('The product contains the characteristics.', 'wc1c-main'));

                $parent_product_id = 0;

                /*
                 * Поиск родительского продукта по идентификатору 1С
                 */
                if('yes' === $this->getOptions('product_sync_by_id', 'yes'))
                {
                    $this->log()->info(__('Parent product search by external ID from 1C.', 'wc1c-main'), ['parent_product_id' => $parent_product_id]);

                    $parent_product_id = $product_factory->findIdsByExternalIdAndCharacteristicId($external_product->getId(), '');

                    if(is_array($parent_product_id)) // todo: обработка нескольких?
                    {
                        $this->log()->warning(__('Several identical parent products were found. The first one is selected.', 'wc1c-main'), ['parent_product_ids' => $parent_product_id]);
                        $parent_product_id = reset($parent_product_id);
                    }

                    $this->log()->debug(__('Parent product search result by external ID from 1C.', 'wc1c-main'), ['parent_product_ids' => $parent_product_id]);
                }

                /**
                 * Поиск идентификатора существующего родительского продукта по внешним алгоритмам
                 *
                 * @param int $parent_product_id Идентификатор найденного продукта
                 * @param ProductDataContract $external_product Данные продукта в CML
                 * @param SchemaAbstract $this
                 * @param Reader $reader Текущий итератор
                 *
                 * @return int|false
                 */
                if(empty($parent_product_id) && has_filter('wc1c_schema_productscml_processing_products_parent_search'))
                {
                    $this->log()->info(__('Parent product search by external algorithms.', 'wc1c-main'), ['parent_product_id' => $parent_product_id]);

                    $parent_product_id = apply_filters('wc1c_schema_productscml_processing_products_parent_search', $parent_product_id, $external_product, $this, $reader);

                    if(is_array($parent_product_id)) // todo: обработка нескольких?
                    {
                        $this->log()->warning(__('Several identical parent products were found. The first one is selected.', 'wc1c-main'), ['parent_product_ids' => $parent_product_id]);
                        $parent_product_id = reset($parent_product_id);
                    }

                    $this->log()->debug(__('Parent product search result by external algorithms.', 'wc1c-main'), ['parent_product_id' => $parent_product_id]);
                }

                if(empty($parent_product_id))
                {
                    if('yes' !== $this->getOptions('products_with_characteristics_simple', 'no'))
                    {
                        $this->log()->info(__('Parent product is not found.', 'wc1c-main'));
                        $this->log()->debug(__('Creating simple products by characteristics is disabled in the settings. Product create skipped.', 'wc1c-main'));
                        return;
                    }

                    $this->log()->debug(__('Creating simple products by characteristics is enabled in the settings. Parent product is not found. Creating simple product by characteristic.', 'wc1c-main'));
                }
                else
                {
                    $internal_product_parent = $product_factory->getProduct($parent_product_id);

                    /*
                     * Родительский продукт не вариативный, превращаем его в вариативный
                     */
                    if(!$internal_product_parent instanceof VariableProduct)
                    {
                        $this->log()->notice(__('Changing the parent product type to variable.', 'wc1c-main'), ['product_id' => $parent_product_id]);

                        $internal_product_parent = new VariableProduct($parent_product_id);

                        $internal_product_parent->save();
                    }

                    $this->log()->info(__('Variation is not found. Creating.', 'wc1c-main'), ['parent_product_id' => $parent_product_id]);

                    $internal_product = new VariationVariableProduct();

                    $internal_product->set_parent_id($parent_product_id);

                    $internal_product->setSchemaId($this->getId());
                    $internal_product->setConfigurationId($this->configuration()->getId());

                    $internal_product->setExternalId($external_product->getId());
                    $internal_product->setExternalCharacteristicId($external_product->getCharacteristicId());

                    $internal_product_id = $internal_product->save();

                    $this->log()->debug(__('The creation of the variation is completed.', 'wc1c-main'), ['parent_product_id' => $parent_product_id, 'product_variation_id' => $internal_product_id]);
                }
			}
			else
			{
				$this->log()->info(__('The product is simple. Creating.', 'wc1c-main'));
			}

            if(!isset($internal_product))
            {
                /**
                 * Создание простого продукта с заполнением данных
                 *
                 * @var $internal_product ProductContract
                 */
                $internal_product = new SimpleProduct();

                $internal_product->setSchemaId($this->getId());
                $internal_product->setConfigurationId($this->configuration()->getId());
                $internal_product->setExternalId($external_product->getId());

                if($external_product->hasCharacteristicId())
                {
                    $internal_product->setExternalCharacteristicId($external_product->getCharacteristicId());
                }
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
                $this->log()->debug(__('Assignment of data for the created product according to external algorithms.', 'wc1c-main'));

				$internal_product = apply_filters('wc1c_schema_productscml_processing_products_item_before_save', $internal_product, $external_product, 'create', $reader);
			}

			$internal_product = $this->setProductTimes($internal_product);

            $internal_product->update_meta_data('_wc1c_time_catalog', (int)$this->configuration()->getMeta('_catalog_full_time'));

			try
			{
				$id = $internal_product->save();

				$this->log()->notice(__('The product is created.', 'wc1c-main'), ['product_id' => $id, 'product_type' => $internal_product->get_type()]);
			}
			catch(\Throwable $e)
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
                $this->log()->info(__('Assignment of data for the created product according to external algorithms after saving.', 'wc1c-main'));

                $internal_product = apply_filters('wc1c_schema_productscml_processing_products_item_after_save', $internal_product, $external_product, 'create', $reader);

				try
				{
					$id = $internal_product->save();

					$this->log()->info(__('The product has been updated using external algorithms.', 'wc1c-main'), ['product_id' => $id, 'product_type' => $internal_product->get_type()]);
				}
				catch(\Throwable $e)
				{
					throw new Exception($e->getMessage());
				}
			}

			return;
		}

        $this->log()->info(__('Product is found. Updating.', 'wc1c-main'));

		/**
		 * Обновление существующих продуктов отключено
		 */
		if('yes' !== $this->getOptions('products_update', 'no'))
		{
			$this->log()->debug(__('Products update is disabled in settings. Product update skipped.', 'wc1c-main'), ['product_id' => $product_id]);
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
			$this->log()->warning(__('The product is created from a different configuration. Update skipped.', 'wc1c-main'), ['product_id' => $product_id]);
			return;
		}

		/*
		 * Пропуск продуктов созданных из других схем
		 */
		if('yes' === $this->getOptions('products_update_only_schema', 'no') && (string)$update_product->getSchemaId() !== $this->getId())
		{
			$this->log()->warning(__('The product is created from a different schema. Update skipped.', 'wc1c-main'), ['product_id' => $product_id]);
			return;
		}

		/*
		 * Пропуск обновления продуктов из корзины, не помеченных к удалению в 1С
		 */
		if
        (
            'trash' === $update_product->get_status()
            && false === $external_product->hasDeleted()
            && 'yes' !== $this->getOptions('products_update_use_delete_mark', 'no')
        )
		{
			$this->log()->warning(__('The use of products from trash is disabled. Updating skipped.', 'wc1c-main'));
			return;
		}

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
            $this->log()->debug(__('Assignment of data for the updated product according to external algorithms.', 'wc1c-main'));

			$update_product = apply_filters('wc1c_schema_productscml_processing_products_item_before_save', $update_product, $external_product, 'update', $reader);
		}

		$update_product = $this->setProductTimes($update_product);

        $update_product->update_meta_data('_wc1c_time_catalog', (int)$this->configuration()->getMeta('_catalog_full_time'));

		try
		{
            $id = $update_product->save();

            $this->log()->notice(__('Product update has been successfully completed.', 'wc1c-main'), ['product_id' => $id, 'product_type' => $update_product->get_type()]);
        }
		catch(\Throwable $e)
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
            $this->log()->info(__('Assignment of data for the updated product according to external algorithms after saving.', 'wc1c-main'));

            $update_product = apply_filters('wc1c_schema_productscml_processing_products_item_after_save', $update_product, $external_product, 'update', $reader);

			try
			{
                $id = $update_product->save();

                $this->log()->notice(__('Product update after assigning data using external algorithms has been successfully completed.', 'wc1c-main'), ['product_id' => $id, 'product_type' => $update_product->get_type()]);
			}
			catch(\Throwable $e)
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

        $internal_offer_id = 0;
		$product_factory = new Factory();

        /*
         * Поиск продукта по идентификатору 1С
         */
        if('yes' === $this->getOptions('product_sync_by_id', 'yes'))
        {
            $this->log()->info(__('Product search by external ID from 1C.', 'wc1c-main'), ['product_id' => $internal_offer_id]);

            $internal_offer_id = $product_factory->findIdsByExternalIdAndCharacteristicId($external_offer->getId(), $external_offer->getCharacteristicId()); // todo: Учитывать каталог при поиске

            if(is_array($internal_offer_id)) // todo: обработка нескольких?
            {
                $this->log()->warning(__('Several identical products were found. The first one is selected.', 'wc1c-main'), ['product_ids' => $internal_offer_id]);
                $internal_offer_id = reset($internal_offer_id);
            }

            $this->log()->debug(__('Product search result by external ID from 1C.', 'wc1c-main'), ['product_id' => $internal_offer_id]);
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
            $this->log()->info(__('Product not found. Search by external algorithms.', 'wc1c-main'), ['product_id' => $internal_offer_id]);

            $internal_offer_id = apply_filters('wc1c_schema_productscml_processing_offers_search', $internal_offer_id, $external_offer, $reader);

            if(is_array($internal_offer_id)) // todo: обработка нескольких?
            {
                $this->log()->warning(__('Several identical products were found. The first one is selected.', 'wc1c-main'), ['product_ids' => $internal_offer_id]);
                $internal_offer_id = reset($internal_offer_id);
            }

            $this->log()->debug(__('Product search result by external algorithms.', 'wc1c-main'), ['product_id' => $internal_offer_id]);
		}

        /**
         * Продукт не найден
         */
        if(empty($internal_offer_id))
        {
            $this->log()->info(__('Product not found.', 'wc1c-main'), ['offer' => $external_offer->getData()]);

            /**
             * Предложение продукта с характеристикой
             * ---
             * Проверка наличия родительского продукта для продукта с характеристикой, и
             * если родительского продукта нет, пропускаем обработку.
             * Исключение составляет включенная возможность создания родительского продукта на основе первой характеристики.
             */
            if($external_offer->hasCharacteristicId())
            {
                $this->log()->info(__('The product contains the characteristics.', 'wc1c-main'));

                $parent_product_id = 0;

                /*
                 * Поиск родительского продукта по идентификатору 1С
                 */
                if('yes' === $this->getOptions('product_sync_by_id', 'yes'))
                {
                    $parent_product_id = $product_factory->findIdsByExternalIdAndCharacteristicId($external_offer->getId(), '');

                    $this->log()->debug(__('Parent product search result by external code from 1C.', 'wc1c-main'), ['parent_product_ids' => $parent_product_id]);

                    if(is_array($parent_product_id)) // todo: обработка нескольких?
                    {
                        $this->log()->notice(__('Several identical parent products were found. The first one is selected.', 'wc1c-main'), ['parent_product_ids' => $parent_product_id]);
                        $parent_product_id = reset($parent_product_id);
                    }
                }

                /**
                 * Поиск идентификатора существующего родительского продукта по внешним алгоритмам
                 *
                 * @param int $parent_product_id Идентификатор найденного продукта
                 * @param ProductDataContract $external_offer Данные продукта в CML
                 * @param SchemaAbstract $this
                 * @param Reader $reader Текущий итератор
                 *
                 * @return int|false
                 */
                if(empty($parent_product_id) && has_filter('wc1c_schema_productscml_processing_products_parent_search'))
                {
                    $parent_product_id = apply_filters('wc1c_schema_productscml_processing_products_parent_search', $parent_product_id, $external_offer, $this, $reader);

                    $this->log()->debug(__('Parent product search result by external algorithms.', 'wc1c-main'), ['parent_product_ids' => $parent_product_id]);
                }

                if(empty($parent_product_id))
                {
                    if('yes' !== $this->getOptions('products_with_characteristics_simple', 'no'))
                    {
                        $this->log()->info(__('Parent product is not found.', 'wc1c-main'));
                        $this->log()->notice(__('Creating simple products by characteristics is disabled in the settings. Processing skipped.', 'wc1c-main'));
                        return;
                    }

                    $this->log()->info(__('Creating simple products by characteristics is enabled in the settings. Parent product is not found. Creating simple product by characteristic.', 'wc1c-main'));
                }
                else
                {
                    $internal_product_parent = $product_factory->getProduct($parent_product_id);

                    /*
                     * Родительский продукт не вариативный, превращаем его в вариативный
                     */
                    if(!$internal_product_parent instanceof VariableProduct)
                    {
                        $this->log()->info(__('Changing the parent product type to variable.', 'wc1c-main'), ['product_id' => $parent_product_id]);

                        $internal_product_parent = new VariableProduct($parent_product_id);

                        $internal_product_parent->save();
                    }

                    $this->log()->info(__('Variation is not found. Creating.', 'wc1c-main'), ['parent_product_id' => $parent_product_id]);

                    $internal_offer = new VariationVariableProduct();

                    $internal_offer->set_parent_id($parent_product_id);

                    $internal_offer->setSchemaId($this->getId());
                    $internal_offer->setConfigurationId($this->configuration()->getId());

                    $internal_offer->setExternalId($external_offer->getId());
                    $internal_offer->setExternalCharacteristicId($external_offer->getCharacteristicId());

                    $internal_offer_id = $internal_offer->save();

                    $this->log()->debug(__('The creation of the variation is completed.', 'wc1c-main'), ['parent_product_id' => $parent_product_id, 'product_variation_id' => $internal_offer_id]);
                }
            }
            else
            {
                $this->log()->notice(__('Offer update skipped.', 'wc1c-main'), ['offer' => $external_offer->getData()]);
                return;
            }
        }

        /*
         * Экземпляр обновляемого продукта по найденному идентификатору продукта
         */
        if(!isset($internal_offer))
        {
            $internal_offer = $product_factory->getProduct($internal_offer_id);
        }

        /*
         * Пропуск продуктов созданных из других конфигураций
         */
        if('yes' === $this->getOptions('products_update_only_configuration', 'no') && (int)$internal_offer->getConfigurationId() !== $this->configuration()->getId())
        {
            $this->log()->warning(__('The product is created from a different configuration. Update skipped.', 'wc1c-main'), ['offer_id' => $internal_offer_id]);
            return;
        }

        /*
         * Пропуск продуктов созданных из других схем
         */
        if('yes' === $this->getOptions('products_update_only_schema', 'no') && (string)$internal_offer->getSchemaId() !== $this->getId())
        {
            $this->log()->warning(__('The product is created from a different schema. Update skipped.', 'wc1c-main'), ['offer_id' => $internal_offer_id]);
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

		$internal_offer = $this->setProductTimes($internal_offer);

		try
		{
			$internal_offer->save();
		}
		catch(\Throwable $e)
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
		if(false === $reader->isElement())
		{
			return;
		}

		if(is_null($reader->offers_package))
		{
			$reader->offers_package = new OffersPackage();
		}

		if($reader->nodeName === 'ПакетПредложений')
		{
			$only_changes = $reader->xml_reader->getAttribute('СодержитТолькоИзменения') ?: true;
			if($only_changes === 'false')
			{
				$only_changes = false;
			}
			$reader->offers_package->setOnlyChanges($only_changes);

			if($only_changes)
			{
				$this->log()->debug(__('The offer package object contains only the changes.', 'wc1c-main'));
			}
		}
		elseif($reader->nodeName === 'ИзмененияПакетаПредложений')
		{
			$this->log()->debug(__('The offer package object contains only the changes.', 'wc1c-main'));
			$reader->offers_package->setOnlyChanges(true);
		}

		if(($reader->parentNodeName === 'ПакетПредложений' || $reader->parentNodeName === 'ИзмененияПакетаПредложений'))
		{
			switch($reader->nodeName)
			{
				case 'Ид':
					$id = $reader->decoder()->normalizeId($reader->xml_reader->readString());
					$reader->offers_package->setId($id);
					break;
				case 'Наименование':
					$reader->offers_package->setName($reader->xml_reader->readString());
					break;
				case 'ИдКаталога':
					$id = $reader->decoder()->normalizeId($reader->xml_reader->readString());
					$reader->offers_package->setCatalogId($id);
					break;
				case 'ИдКлассификатора':
					$id = $reader->decoder()->normalizeId($reader->xml_reader->readString());
					$reader->offers_package->setClassifierId($id);
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
				case 'Склады':
					$warehouses = $reader->decoder()->process('warehouses', $reader->xml_reader->readOuterXml());
					$reader->offers_package->setWarehouses($warehouses);
					$reader->next();
					break;
			}
		}

        if($reader->nodeName === 'Предложения')
        {
			if(false === $reader->offers_package->isOnlyChanges())
			{
				$this->log()->info(__('Saving the offer package to configuration meta data.', 'wc1c-main'), ['filetype' => $reader->getFiletype()]);

				$this->configuration()->addMetaData('offers_package:' . $reader->offers_package->getId(), $reader->offers_package, true);
				$this->configuration()->saveMetaData();
			}

			if(empty($reader->offers_package->getPriceTypes()))
			{
				$price_types = $this->configuration()->getMeta('classifier-price-types:' . $reader->offers_package->getClassifierId());
				if(is_array($price_types))
				{
					$reader->offers_package->setPriceTypes($price_types);
				}
			}
        }

		if($reader->parentNodeName === 'Предложения' && $reader->nodeName === 'Предложение')
		{
			$offer_xml = new SimpleXMLElement($reader->xml_reader->readOuterXml());

			try
			{
				$offer = $reader->decoder->process('offer', $offer_xml);
			}
			catch(\Throwable $e)
			{
				$this->log()->warning(__('An exception was thrown decode the offer.', 'wc1c-main'), ['exception' => $e]);
				return;
			}

			/**
			 * Внешняя фильтрация перед непосредственной обработкой
			 *
			 * @param ProductDataContract $offer
			 * @param Reader $reader
			 * @param SchemaAbstract $this
			 * @param SimpleXMLElement $offer_xml
			 */
			if(has_filter('wc1c_schema_productscml_processing_offers'))
			{
				$offer = apply_filters('wc1c_schema_productscml_processing_offers', $offer, $reader, $this, $offer_xml);

				$this->log()->info(__('The offer has been changed according to external algorithms.', 'wc1c-main'));
			}

			if(!$offer instanceof ProductDataContract)
			{
				$this->log()->warning(__('Offer !instanceof ProductDataContract. Processing skipped.', 'wc1c-main'), ['data' => $offer]);
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
			catch(\Throwable $e)
			{
				$this->log()->warning(__('An exception was thrown while processing the offer.', 'wc1c-main'), ['exception' => $e]);
			}

			$reader->next();
		}
	}
}
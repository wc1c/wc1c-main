<?php namespace Wc1c\Main\Schemas\Productscleanercml;

defined('ABSPATH') || exit;

use XMLReader;
use Wc1c\Cml\Contracts\ClassifierDataContract;
use Wc1c\Cml\Contracts\ProductDataContract;
use Wc1c\Cml\Decoder;
use Wc1c\Cml\Entities\Catalog;
use Wc1c\Cml\Reader;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Schemas\Abstracts\SchemaAbstract;
use Wc1c\Wc\Products\Factory;

/**
 * Core
 *
 * @package Wc1c\Main\Schemas\Productscleanercml
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
		$this->setId('productscleanercml');
		$this->setVersion('0.2.0');

		$this->setName(__('Cleaning of products via CommerceML', 'wc1c-main'));
		$this->setDescription(__('Cleaning of existing products in WooCommerce according to the nomenclature from 1C via the CommerceML protocol.', 'wc1c-main'));
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
	 * @return boolean
	 */
	public function init(): bool
	{
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

			add_action('wc1c_schema_productscleanercml_file_processing_read', [$this, 'processingTimer'], 5, 1);

			add_action('wc1c_schema_productscleanercml_file_processing_read', [$this, 'processingClassifier'], 10, 1);
			add_action('wc1c_schema_productscleanercml_file_processing_read', [$this, 'processingCatalog'], 20, 1);

			add_action('wc1c_schema_productscleanercml_processing_products_item', [$this, 'processingProductsItem'], 10, 2);
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

		if(has_filter('wc1c_schema_productscleanercml_file_processing_decoder'))
		{
			$decoder = apply_filters('wc1c_schema_productscleanercml_file_processing_decoder', $decoder, $this);
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

		if(has_filter('wc1c_schema_productscleanercml_file_processing_reader'))
		{
			$reader = apply_filters('wc1c_schema_productscleanercml_file_processing_reader', $reader, $this);
		}

		while($reader->read())
		{
			try
			{
				do_action('wc1c_schema_productscleanercml_file_processing_read', $reader, $this);
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
	public function processingTimer($reader)
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
			if(has_filter('wc1c_schema_productscleanercml_processing_classifier'))
			{
				$classifier = apply_filters('wc1c_schema_productscleanercml_processing_classifier', $classifier, $this);
			}

			if(!$classifier instanceof ClassifierDataContract)
			{
				$this->log()->debug(__('Classifier !instanceof ClassifierDataContract. Skip processing.', 'wc1c-main'), ['data' => $classifier]);
				return;
			}

			$reader->classifier = $classifier;

			try
			{
				do_action('wc1c_schema_productscleanercml_processing_classifier_item', $classifier, $reader, $this);
			}
			catch(Exception $e)
			{
				$this->log()->warning(__('An exception was thrown while saving the classifier.', 'wc1c-main'), ['exception' => $e]);
			}

			$reader->next();
		}
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
			if(has_filter('wc1c_schema_productscleanercml_processing_products'))
			{
				$product = apply_filters('wc1c_schema_productscleanercml_processing_products', $product, $reader, $this);
			}

			if(!$product instanceof ProductDataContract)
			{
				$this->log()->debug(__('Product !instanceof ProductDataContract. Skip processing.', 'wc1c-main'), ['data' => $product]);
				return;
			}

			try
			{
				do_action('wc1c_schema_productscleanercml_processing_products_item', $product, $reader, $this);
			}
			catch(Exception $e)
			{
				$this->log()->warning(__('An exception was thrown while saving the product.', 'wc1c-main'), ['exception' => $e]);
			}

			$reader->next();
		}
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
	public function processingProductsItem($external_product, $reader)
	{
		$this->log()->info(__('Processing a product from a catalog of products.', 'wc1c-main'), ['product_id' => $external_product->getId(), 'product_characteristic_id' => $external_product->getCharacteristicId()]);

		if('yes' !== $this->getOptions('clean', 'no'))
		{
			$this->log()->info(__('Cleaning of products is disabled. Processing skipped.', 'wc1c-main'), ['product_id' => $external_product->getId(), 'product_characteristic_id' => $external_product->getCharacteristicId()]);
			return;
		}

		$product_id = 0;
		$product_factory = new Factory();

		/*
		 * Поиск продукта по идентификатору 1С
		 */
		if('yes' === $this->getOptions('sync_by_id', 'yes'))
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
		if(has_filter('wc1c_schema_productscleanercml_processing_products_search'))
		{
			$product_id = apply_filters('wc1c_schema_productscleanercml_processing_products_search', $product_id, $external_product, $this, $reader);
			$this->log()->debug(__('Product search result by external algorithms.', 'wc1c-main'), ['product_ids' => $product_id]);
		}

		/**
		 * Ни один продукт не найден
		 */
		if(0 === $product_id)
		{
			$this->log()->info(__('Product is not found.', 'wc1c-main'));
			return;
		}

		/*
		 * Экземпляр продукта по найденному идентификатору продукта
		 */
		$cleaning_product = $product_factory->getProduct($product_id);

		/**
		 * Окончательное удаление
		 */
		if('yes' === $this->getOptions('clean_final', 'no'))
		{
			$cleaning_product->delete(true);
		}
		else
		{
			$cleaning_product->delete(false);
		}
	}
}
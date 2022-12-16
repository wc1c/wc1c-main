<?php namespace Wc1c\Cml;

defined('ABSPATH') || exit;

use XMLReader;
use Wc1c\Main\Exceptions\RuntimeException;
use Wc1c\Cml\Entities\OffersPackage;
use Wc1c\Cml\Contracts\CatalogDataContract;
use Wc1c\Cml\Contracts\ClassifierDataContract;

/**
 * Reader
 *
 * @package Wc1c\Cml
 */
class Reader
{
	use Utility;

	/**
	 * @var XMLReader
	 */
	public $xml_reader = null;

	/**
	 * @var Decoder
	 */
	public $decoder = null;

	/**
	 * @var string Filetype
	 */
	public $filetype;

	/**
	 * @var string File to read
	 */
	protected $file;

	/**
	 * @var integer Current position
	 */
	public $position = 0;

	/**
	 * @var array Counter elements
	 */
	public $elements = [];

	/**
	 * @var bool Mark as full file read
	 */
	public $ready = false;

	/**
	 * @var int Current depth
	 */
	public $depth = 0;

	/**
	 * @var int Previous depth
	 */
	public $prevDepth = 0;

	/**
	 * @var string|null Parent node name
	 */
	public $parentNodeName = null;

	/**
	 * @var string|null Previous node name
	 */
	public $prevNodeName = null;

	/**
	 * @var string|null Next node name
	 */
	public $nodeName = null;

	/**
	 * @var string
	 */
	public $schema_version = '';

	/**
	 * @var string Formation date
	 */
	public $formation_date = '';

	/**
	 * @var ClassifierDataContract Текущий классификатор, если присутствует в файле
	 */
	public $classifier = null;

	/**
	 * @var CatalogDataContract Текущий каталог товаров, если присутствует в файле
	 */
	public $catalog = null;

	/**
	 * @var OffersPackage Текущий пакет предложений, если присутствует в файле
	 */
	public $offers_package = null;

	/**
	 * Reader constructor.
	 *
	 * @param string $file_path
	 * @param Decoder|null $decoder
	 *
	 * @throws RuntimeException
	 */
	public function __construct(string $file_path, Decoder $decoder)
	{
		if(!defined('LIBXML_VERSION'))
		{
			throw new RuntimeException('LIBXML_VERSION is not defined.');
		}

		if(!function_exists('libxml_use_internal_errors'))
		{
			throw new RuntimeException('libxml_use_internal_errors is not exists.');
		}

		libxml_use_internal_errors(true);

		$this->decoder = $decoder;

		$this->xml_reader = new XMLReader();

		if('' !== $file_path)
		{
			$this->open($file_path);
		}
	}

	/**
	 * Reader destructor.
	 */
	public function __destruct()
	{
		$this->close();
	}

	/**
	 * @return Decoder
	 */
	public function decoder(): Decoder
	{
		if(!$this->decoder instanceof Decoder)
		{
			$this->decoder = new Decoder();
		}

		return $this->decoder;
	}

	/**
	 * @param string $file_path Path to CML file
	 *
	 * @throws RuntimeException
	 */
	public function open(string $file_path)
	{
		$reader_result = false;

		if(is_file($file_path))
		{
			$reader_result = $this->xml_reader->open($file_path);
		}

		if(false === $reader_result)
		{
			throw new RuntimeException('File is not open.');
		}

		$this->file = $file_path;

		$this->setFiletype($this->cmlDetectFileType($file_path));

		if($this->getFiletype() === '')
		{
			throw new RuntimeException('CommerceML filetype is not valid.');
		}
	}

	/**
	 * @return bool
	 */
	public function close(): bool
	{
		if($this->xml_reader instanceof XMLReader)
		{
			if($this->xml_reader->close())
			{
				$this->xml_reader = null;
				return true;
			}
			return false;
		}
		return true;
	}

	/**
	 * @return string
	 */
	public function getFiletype(): string
	{
		return $this->filetype;
	}

	/**
	 * @param string $filetype
	 */
	public function setFiletype(string $filetype)
	{
		$this->filetype = $filetype;
	}

	/**
	 * @return bool
	 */
	public function read(): bool
	{
		if($this->xml_reader->nodeType === XMLReader::ELEMENT)
		{
			$this->prevNodeName = $this->xml_reader->name;
			$this->prevDepth = $this->xml_reader->depth;
		}

		if($this->xml_reader->nodeType === XMLReader::END_ELEMENT && $this->xml_reader->name === 'КоммерческаяИнформация')
		{
			$this->ready = true;
		}

		if($this->xml_reader->read())
		{
			++$this->position;

			if(!isset($this->elements[$this->xml_reader->name]))
			{
				$this->elements[$this->xml_reader->name] = 0;
			}

			$this->elements[$this->xml_reader->name] = $this->elements[$this->xml_reader->name] ? $this->elements[$this->xml_reader->name] + 1 : 1;

			$this->depth = $this->xml_reader->depth;
			$this->nodeName = $this->xml_reader->name;

			if($this->xml_reader->nodeType === XMLReader::ELEMENT)
			{
				if($this->schema_version === '' && $this->depth === 0 && $this->xml_reader->name === 'КоммерческаяИнформация')
				{
					$this->schema_version = $this->xml_reader->getAttribute('ВерсияСхемы');
					$this->formation_date = $this->xml_reader->getAttribute('ДатаФормирования');
				}

				if(empty($this->parentNodeName) && $this->depth > 0)
				{
					$this->parentNodeName = $this->nodeName;
				}

				if(!empty($this->parentNodeName) && $this->depth - 1 === $this->prevDepth && $this->depth < 4)
				{
					$this->parentNodeName = $this->prevNodeName;
				}
			}

			return true;
		}
		return false;
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function next($name = null): bool
	{
		if(is_null($name))
		{
			return $this->xml_reader->next();
		}

		return $this->xml_reader->next($name);
	}

	/**
	 * Return node-type as human-readable string
	 *
	 * @param string|null $node_type
	 *
	 * @return string
	 */
	public function getNodeTypeName(string $node_type = null): string
	{
		$types_map =
		[
			XMLReader::NONE => 'NONE',
			XMLReader::ELEMENT => 'ELEMENT',
			XMLReader::ATTRIBUTE => 'ATTRIBUTE',
			XMLReader::TEXT => 'TEXT',
			XMLReader::CDATA => 'CDATA',
			XMLReader::ENTITY_REF => 'ENTITY_REF',
			XMLReader::ENTITY => 'ENTITY',
			XMLReader::PI => 'PI',
			XMLReader::COMMENT => 'COMMENT',
			XMLReader::DOC => 'DOC',
			XMLReader::DOC_TYPE => 'DOC_TYPE',
			XMLReader::DOC_FRAGMENT => 'DOC_FRAGMENT',
			XMLReader::NOTATION => 'NOTATION',
			XMLReader::WHITESPACE => 'WHITESPACE',
			XMLReader::SIGNIFICANT_WHITESPACE => 'SIGNIFICANT_WHITESPACE',
			XMLReader::END_ELEMENT => 'END_ELEMENT',
			XMLReader::END_ENTITY => 'END_ENTITY',
			XMLReader::XML_DECLARATION => 'XML_DECLARATION',
		];

		return $types_map[$node_type];
	}

	/**
	 * Debug method
	 */
	public function dump()
	{
		$reader = $this->xml_reader;
		$nodeType = $reader->nodeType;
		$extra = '';

		if($reader->nodeType === XMLReader::ELEMENT)
		{
			$extra = '<' . $reader->name . '> ';
			$extra .= sprintf("(isEmptyElement: %s) ", $reader->isEmptyElement ? 'Yes' : 'No');
		}

		if ($reader->nodeType === XMLReader::END_ELEMENT)
		{
			$extra = '</' . $reader->name . '> ';
		}

		if($reader->nodeType === XMLReader::ATTRIBUTE)
		{
			$str = $reader->value;
			$len = strlen($str);
			if($len > 40)
			{
				$str = substr($str, 0, 31) . '...';
			}
			$str = strtr($str, array("\n" => '\n'));
			$extra = sprintf('%s = (%d) "%s" ', $reader->name, strlen($str), $str);
		}

		if($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::WHITESPACE || $reader->nodeType === XMLReader::SIGNIFICANT_WHITESPACE)
		{
			$str = $reader->readString();
			$len = strlen($str);
			if($len > 20)
			{
				$str = substr($str, 0, 17) . '...';
			}
			$str = strtr($str, array("\n" => '\n'));
			$extra = sprintf('(%d) "%s" ', strlen($str), $str);
		}

		$nodeTypeName = $this->getNodeTypeName($nodeType);

		$label = sprintf("(#%d) %s %s", $nodeType, $nodeTypeName, $extra);

		printf("%s%s\n", str_repeat('  ', $reader->depth), $label);
	}
}
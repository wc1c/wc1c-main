<?php namespace Wc1c\Main\Schemas\Abstracts;

defined('ABSPATH') || exit;

use Wc1c\Main\Configuration;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Log\Logger;
use Wc1c\Main\Schemas\Contracts\SchemaContract;

/**
 * SchemaAbstract
 *
 * @package Wc1c\Main\Abstracts
 */
abstract class SchemaAbstract implements SchemaContract
{
	/**
	 * @var bool Initialized flag
	 */
	private $initialized = false;

	/**
	 * @var string Unique schema id
	 */
	private $id = '';

	/**
	 * @var Configuration Current configuration
	 */
	private $configuration;

	/**
	 * @var array Unique schema options
	 */
	private $options = [];

	/**
	 * @var string Unique prefix wc1c_prefix_{schema_id}_{configuration_id}
	 */
	private $prefix = '';

	/**
	 * @var string Unique configuration prefix wc1c_configuration_{configuration_id}
	 */
	private $configuration_prefix = '';

	/**
	 * @var string Current version
	 */
	private $version = '';

	/**
	 * @var string Name
	 */
	private $name = '';

	/**
	 * @var string Description
	 */
	private $description = '';

	/**
	 * @var string Schema Author
	 */
	private $author = 'WC1C team';

	/**
	 * @throws Exception
	 *
	 * @return mixed
	 */
	abstract public function init();

	/**
	 * @return bool
	 */
	public function isInitialized()
	{
		return $this->initialized;
	}

	/**
	 * @param bool $initialized
	 */
	public function setInitialized($initialized)
	{
		$this->initialized = $initialized;
	}

	/**
	 * Set schema id
	 *
	 * @param $id
	 *
	 * @return $this
	 */
	protected function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Get schema id
	 *
	 * @param bool $lower
	 *
	 * @return string
	 */
	public function getId($lower = true)
	{
		if($lower)
		{
			return strtolower($this->id);
		}

		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	protected function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	protected function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getAuthor()
	{
		return $this->author;
	}

	/**
	 * @param string $author
	 */
	protected function setAuthor($author)
	{
		$this->author = $author;
	}

	/**
	 * Set schema options
	 *
	 * @param $options
	 *
	 * @return $this
	 */
	public function setOptions($options)
	{
		$this->options = $options;

		return $this;
	}

	/**
	 * Get schema options
	 *
	 * @param string $key - unique option id
	 * @param null $default - false for error
	 *
	 * @return array|bool|null
	 */
	public function getOptions($key = '', $default = null)
	{
		if($key !== '')
		{
			if(is_array($this->options) && array_key_exists($key, $this->options))
			{
				return $this->options[$key];
			}

			if(false === is_null($default))
			{
				return $default;
			}

			return false;
		}

		return $this->options;
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param string $version
	 */
	protected function setVersion($version)
	{
		$this->version = $version;
	}

	/**
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	/**
	 * @param string $prefix
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}

	/**
	 * @return string
	 */
	public function getConfigurationPrefix()
	{
		return $this->configuration_prefix;
	}

	/**
	 * @param string $configuration_prefix
	 */
	public function setConfigurationPrefix($configuration_prefix)
	{
		$this->configuration_prefix = $configuration_prefix;
	}

	/**
	 * @return string Unique schema prefix wc1c_schema_{schema_id}
	 */
	public function getSchemaPrefix()
	{
		return 'wc1c_schema_' . $this->getId();
	}

	/**
	 * @return Configuration
	 */
	public function configuration()
	{
		return $this->configuration;
	}

	/**
	 * @param Configuration|null $configuration
	 */
	public function setConfiguration($configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * Logger
	 *
	 * @return Logger
	 */
	public function log($channel = 'configurations')
	{
		$name = $this->configuration()->getUploadDirectory('logs') . DIRECTORY_SEPARATOR . $channel;

		if($channel === 'configurations')
		{
			$name = $this->configuration()->getUploadDirectory('logs') . DIRECTORY_SEPARATOR . 'main';
		}

		if($channel === 'schemas')
		{
			$name = $this->configuration()->getSchema();
		}

		$hard_level = $this->getOptions('logger_level', 'logger_level');

		if('logger_level' === $hard_level)
		{
			$hard_level = null;
		}

		return wc1c()->log($channel, $name, $hard_level);
	}
}
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
     * @var string Unique configuration prefix  wc1c_schema_{schema_id}
     */
    private $schema_prefix = '';

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
	public function isInitialized(): bool
    {
		return $this->initialized;
	}

    /**
     * @param bool $initialized
     *
     * @return SchemaAbstract
     */
	public function setInitialized(bool $initialized): SchemaAbstract
    {
		$this->initialized = $initialized;

        return $this;
	}

	/**
	 * Set schema id
	 *
	 * @param $id
	 *
	 * @return SchemaAbstract
	 */
	protected function setId($id): SchemaAbstract
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
	public function getId(bool $lower = true): string
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
	public function getName(): string
    {
		return $this->name;
	}

    /**
     * @param string $name
     *
     * @return SchemaAbstract
     */
	protected function setName(string $name): SchemaAbstract
    {
		$this->name = $name;

        return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string
    {
		return $this->description;
	}

    /**
     * @param string $description
     *
     * @return SchemaAbstract
     */
	protected function setDescription(string $description): SchemaAbstract
    {
		$this->description = $description;

        return $this;
	}

	/**
	 * @return string
	 */
	public function getAuthor(): string
    {
		return $this->author;
	}

    /**
     * @param string $author
     *
     * @return SchemaAbstract
     */
	protected function setAuthor(string $author): SchemaAbstract
    {
		$this->author = $author;

        return $this;
	}

	/**
	 * Set schema options
	 *
	 * @param $options
	 *
	 * @return $this
	 */
	public function setOptions($options): SchemaAbstract
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
	public function getOptions(string $key = '', $default = null)
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
	public function getVersion(): string
    {
		return $this->version;
	}

    /**
     * @param string $version
     * @return SchemaAbstract
     */
	protected function setVersion(string $version): SchemaAbstract
    {
		$this->version = $version;

        return $this;
	}

	/**
	 * @return string
	 */
	public function getPrefix(): string
    {
		return $this->prefix;
	}

	/**
	 * @param string $prefix
	 */
	public function setPrefix(string $prefix)
	{
		$this->prefix = $prefix;
	}

	/**
	 * @return string
	 */
	public function getConfigurationPrefix(): string
    {
		return $this->configuration_prefix;
	}

    /**
     * @param string $configuration_prefix
     *
     * @return SchemaAbstract
     */
	public function setConfigurationPrefix(string $configuration_prefix): SchemaAbstract
    {
		$this->configuration_prefix = $configuration_prefix;

        return $this;
	}

	/**
	 * @return string Unique schema prefix wc1c_schema_{schema_id}
	 */
	public function getSchemaPrefix(): string
    {
        if(empty($this->schema_prefix))
        {
            return 'wc1c_schema_' . $this->getId();
        }

        return $this->schema_prefix;
	}

    /**
     * @param string $schema_prefix
     *
     * @return SchemaAbstract
     */
    public function setSchemaPrefix(string $schema_prefix): SchemaAbstract
    {
        $this->schema_prefix = $schema_prefix;

        return $this;
    }

	/**
	 * @return Configuration
	 */
	public function configuration(): Configuration
    {
		return $this->configuration;
	}

	/**
	 * @param Configuration $configuration
	 */
	public function setConfiguration(Configuration $configuration)
	{
		$this->configuration = $configuration;
	}

    /**
     * Logger
     *
     * @param string $channel
     *
     * @return Logger
     */
	public function log(string $channel = 'configurations'): Logger
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
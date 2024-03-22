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
     * @return SchemaContract
     */
	public function setInitialized(bool $initialized): SchemaContract
    {
		$this->initialized = $initialized;

        return $this;
	}

	/**
	 * Set schema id
	 *
	 * @param $id
	 *
	 * @return SchemaContract
	 */
	public function setId($id): SchemaContract
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
     * @return SchemaContract
     */
	public function setName(string $name): SchemaContract
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
     * @return SchemaContract
     */
	public function setDescription(string $description): SchemaContract
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
     * @return SchemaContract
     */
	public function setAuthor(string $author): SchemaContract
    {
		$this->author = $author;

        return $this;
	}

	/**
	 * Set schema options
	 *
	 * @param array $options
	 *
	 * @return $this
	 */
	public function setOptions(array $options): SchemaContract
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
	 * @return mixed
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
     *
     * @return SchemaContract
     */
	public function setVersion(string $version): SchemaContract
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
     * @return SchemaContract
     */
	public function setConfigurationPrefix(string $configuration_prefix): SchemaContract
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
     * @return SchemaContract
     */
    public function setSchemaPrefix(string $schema_prefix): SchemaContract
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

        $files_max = $this->getOptions('logger_files_max', null);
		$hard_level = $this->getOptions('logger_level', 'logger_level');

		if('logger_level' === $hard_level)
		{
			$hard_level = null;
		}

        $params =
        [
            'hard_level' => $hard_level,
            'files_max' => $files_max
        ];

		return wc1c()->log($channel, $name, $params);
	}
}
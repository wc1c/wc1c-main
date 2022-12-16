<?php namespace Wc1c\Main;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Data\Storage;
use Wc1c\Main\Data\Entities\ConfigurationsData;

/**
 * Configuration
 *
 * @package Wc1c\Main
 */
class Configuration extends ConfigurationsData
{
	/**
	 * @var array Default data
	 */
	protected $data =
	[
		'user_id' => 0,
		'name' => '',
		'status' => 'draft',
		'options' => [],
		'schema' => 'productscml',
		'date_create' => null,
		'date_modify' => null,
		'date_activity' => null,
	];

	/**
	 * Configuration constructor.
	 *
	 * @param int $data
	 *
	 * @throws Exception
	 */
	public function __construct($data = 0)
	{
		parent::__construct();

		if(is_numeric($data) && $data > 0)
		{
			$this->setId($data);
		}
		elseif($data instanceof self)
		{
			$this->setId(absint($data->getId()));
		}
		else
		{
			$this->setObjectRead(true);
		}

		$this->storage = Storage::load($this->object_type);

		if($this->getId() > 0)
		{
			$this->storage->read($this);
		}
	}

	/**
	 * Get user id
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getUserId($context = 'view')
	{
		return $this->getProp('user_id', $context);
	}

	/**
	 * Set user id
	 *
	 * @param string $value user_id
	 */
	public function setUserId($value)
	{
		$this->setProp('user_id', $value);
	}

	/**
	 * Get name
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getName($context = 'view')
	{
		return $this->getProp('name', $context);
	}

	/**
	 * Set name
	 *
	 * @param string $value name
	 */
	public function setName($value)
	{
		$this->setProp('name', $value);
	}

	/**
	 * Get status
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getStatus($context = 'view')
	{
		return $this->getProp('status', $context);
	}

	/**
	 * Set status
	 *
	 * @param string $value status
	 */
	public function setStatus($value)
	{
		$this->setProp('status', $value);
	}

	/**
	 * Get options
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return array
	 */
	public function getOptions($context = 'view')
	{
		return $this->getProp('options', $context);
	}

	/**
	 * Set options
	 *
	 * @param array $value options
	 */
	public function setOptions($value)
	{
		$this->setProp('options', $value);
	}

	/**
	 * Get schema
	 *
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return string
	 */
	public function getSchema($context = 'view')
	{
		return $this->getProp('schema', $context);
	}

	/**
	 * Set schema
	 *
	 * @param string $name schema id
	 */
	public function setSchema($name)
	{
		$this->setProp('schema', $name);
	}

	/**
	 * Get created date
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return Datetime|NULL object if the date is set or null if there is no date.
	 */
	public function getDateCreate($context = 'view')
	{
		return $this->getProp('date_create', $context);
	}

	/**
	 * Get modified date
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return Datetime|NULL object if the date is set or null if there is no date.
	 */
	public function getDateModify($context = 'view')
	{
		return $this->getProp('date_modify', $context);
	}

	/**
	 * Get activity date
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return Datetime|NULL object if the date is set or null if there is no date.
	 */
	public function getDateActivity($context = 'view')
	{
		return $this->getProp('date_activity', $context);
	}

	/**
	 * Set created date
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 * If the DateTime string has no timezone or
	 * offset, WordPress site timezone will be assumed. Null if their is no date.
	 *
	 * @throws Exception|\Exception
	 */
	public function setDateCreate($date = null)
	{
		$this->setDateProp('date_create', $date);
	}

	/**
	 * Set modified date
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 * If the DateTime string has no timezone or
	 * offset, WordPress site timezone will be assumed. Null if their is no date.
	 *
	 * @throws Exception
	 */
	public function setDateModify($date = null)
	{
		$this->setDateProp('date_modify', $date);
	}

	/**
	 * Set activity date
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime.
	 * If the DateTime string has no timezone or
	 * offset, WordPress site timezone will be assumed. Null if their is no date.
	 *
	 * @throws Exception
	 */
	public function setDateActivity($date = null)
	{
		$this->setDateProp('date_activity', $date);
	}

	/**
	 * Returns if configuration is active.
	 *
	 * @return bool True if validation passes.
	 */
	public function isActive()
	{
		return $this->isStatus('active');
	}

	/**
	 * Returns if configuration is inactive.
	 *
	 * @return bool True if validation passes.
	 */
	public function isInactive()
	{
		return $this->isStatus('inactive');
	}

	/**
	 * Returns if configuration is draft.
	 *
	 * @return bool True if validation passes.
	 */
	public function isDraft()
	{
		return $this->isStatus('draft');
	}

	/**
	 * Returns if configuration is status.
	 *
	 * @param string $status
	 *
	 * @return bool True if validation passes.
	 */
	public function isStatus($status = 'active')
	{
		return $status === $this->getStatus();
	}

	/**
	 * Returns upload directory for configuration.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function getUploadDirectory($context = 'main')
	{
		$upload_directory = wc1c()->environment()->get('wc1c_configurations_directory') . '/' . $this->getSchema() . '-' . $this->getId();

		if($context === 'logs')
		{
			$upload_directory .= '/logs';
		}

		return $upload_directory;
	}
}
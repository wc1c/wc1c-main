<?php namespace Digiom\Woplucore\Data\Abstracts;

defined('ABSPATH') || exit;

use WP_Error;
use DateTimeZone;
use Digiom\Woplucore\Data\Exceptions\Exception;
use Digiom\Woplucore\Datetime;
use Digiom\Woplucore\Traits\DatetimeUtilityTrait;

/**
 * DataAbstract - Implemented by classes using the same CRUD(s) pattern
 *
 * @package Digiom\Woplucore\Data\Abstracts
 */
abstract class DataAbstract
{
	use DatetimeUtilityTrait;

	/**
	 * @var string This is the name of this object type
	 */
	protected $object_type = 'data';

	/**
	 * @var bool This is false until the object is read from the DB
	 */
	protected $object_read = false;

	/**
	 * @var int Object id
	 */
	private $id = 0;

	/**
	 * @var object Contains a reference to the data storage for this class
	 */
	protected $storage;

	/**
	 * @var array Raw key data
	 */
	protected $data = [];

	/**
	 * @var array Set to _data on construct, so we can track and reset data if needed
	 */
	protected $default_data = [];

	/**
	 * @var array Data changes for this object
	 */
	protected $changes = [];

	/**
	 * @var array Extra data for this object. Name value pairs (name + default value). Used as a standard way for subclasses (like key types) to add additional information to an inherited class.
	 */
	protected $extra_data = [];

	/**
	 * Data constructor.
	 *
	 * @param int $read
	 */
	public function __construct($read = 0)
	{
		$this->data = array_merge($this->data, $this->extra_data);
		$this->default_data = $this->data;
	}

	/**
	 * Prefix for action and filter hooks on data
	 *
	 * @return string
	 */
	protected function getHookPrefix(): string
	{
		return $this->storage->unique_prefix . '_data_' . $this->object_type . '_get_';
	}

	/**
	 * Returns the unique ID for this object
	 *
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * Get the data store
	 *
	 * @return object
	 */
	public function getStorage()
	{
		return $this->storage;
	}

	/**
	 * Delete an object, set the ID to 0, and return result
	 *
	 * @param bool $force_delete should the date be deleted permanently
	 *
	 * @return bool result
	 */
	public function delete(bool $force_delete = false): bool
	{
		if($this->storage)
		{
			$this->storage->delete($this, ['force_delete' => $force_delete]);
			$this->setId(0);

			return true;
		}

		return false;
	}

	/**
	 * Save should create or update based on object existence
	 *
	 * @return int
	 */
	public function save(): int
	{
		if(!$this->storage)
		{
			return $this->getId();
		}

		/**
		 * Trigger action before saving to the DB.
		 * Allows you to adjust object props before save.
		 *
		 * @param DataAbstract $this The object being saved
		 * @param DataAbstract $data_store THe data storage persisting the data
		 */
		do_action($this->storage->unique_prefix . '_data_' . $this->object_type . '_before_object_save', $this, $this->storage);

		if($this->getId())
		{
			$this->storage->update($this);
		}
		else
		{
			$this->storage->create($this);
		}

		/**
		 * Trigger action after saving to the DB
		 *
		 * @param DataAbstract $this The object being saved.
		 * @param DataAbstract $data_store THe data storage persisting the data.
		 */
		do_action($this->storage->unique_prefix . '_data_' . $this->object_type . '_after_object_save', $this, $this->storage);

		return $this->getId();
	}

	/**
	 * Change data to JSON format
	 *
	 * @return string Data in JSON format
	 */
	public function __toString()
	{
		$result = wp_json_encode($this->getData());

		if(!is_string($result))
		{
			$result = '';
		}

		return $result;
	}

	/**
	 * Returns all data for this object
	 *
	 * @return array
	 */
	public function getData(): array
	{
		return array_merge(['id' => $this->getId()], $this->data);
	}

	/**
	 * Returns array of expected data
	 * for this object
	 *
	 * @return array
	 */
	public function getDataKeys(): array
	{
		return array_keys($this->data);
	}

	/**
	 * Returns all "extra" data for an object
	 * (for sub objects like data types)
	 *
	 * @return array
	 */
	public function getExtraDataKeys(): array
	{
		return array_keys($this->extra_data);
	}

	/**
	 * Set ID
	 *
	 * @param int $id ID
	 */
	public function setId($id)
	{
		$this->id = absint($id);
	}

	/**
	 * Set all props to default values
	 */
	public function setDefaults()
	{
		$this->data = $this->default_data;
		$this->changes = [];

		$this->setObjectRead(false);
	}

	/**
	 * Set object read property
	 *
	 * @param boolean $read Should read?
	 */
	public function setObjectRead(bool $read = true)
	{
		$this->object_read = (bool) $read;
	}

	/**
	 * Get object read property
	 *
	 * @return boolean
	 */
	public function getObjectRead(): bool
	{
		return (bool) $this->object_read;
	}

	/**
	 * Set a collection of props in one go, collect any errors, and return the result
	 *
	 * Only sets using public methods
	 *
	 * @param array $props Key value pairs to set. Key is the prop and should map to a setter function name
	 * @param string $context In what context to run this
	 *
	 * @return bool|WP_Error
	 */
	public function setProps(array $props, string $context = 'set')
	{
		$errors = false;

		foreach($props as $prop => $value)
		{
			try
			{
				if(is_null($value) || in_array($prop, ['prop', 'date_prop'], true))
				{
					continue;
				}

				$prop = str_replace(' ', '', ucwords(str_replace('_', ' ', $prop)));

				$setter = "set$prop";

				if(is_callable([$this, $setter]))
				{
					$this->{$setter}($value);
				}
			}
			catch(Exception $e)
			{
				if(!$errors)
				{
					$errors = new WP_Error();
				}

				$errors->add($e->getCode(), $e->getMessage());
			}
		}

		return $errors && count($errors->get_error_codes()) ? $errors : true;
	}

	/**
	 * Sets a prop for a setter method
	 * This storage changes in a special array, so we can track what needs saving the DB later
	 *
	 * @param string $prop Name of prop to set
	 * @param mixed $value Value of the prop
	 */
	protected function setProp(string $prop, $value)
	{
		if(array_key_exists($prop, $this->data))
		{
			if(true === $this->object_read)
			{
				if($value !== $this->data[$prop] || array_key_exists($prop, $this->changes))
				{
					$this->changes[$prop] = $value;
				}
			}
			else
			{
				$this->data[$prop] = $value;
			}
		}
	}

	/**
	 * Return data changes only
	 *
	 * @return array
	 */
	public function getChanges(): array
	{
		return $this->changes;
	}

	/**
	 * Merge changes with data and clear
	 */
	public function applyChanges()
	{
		$this->data = array_replace_recursive($this->data, $this->changes);
		$this->changes = [];
	}

	/**
	 * Gets a prop for a getter method
	 *
	 * Gets the value from either current pending changes, or the data itself
	 * Context controls what happens to the value before it's returned
	 *
	 * @param string $prop Name of prop to get
	 * @param string $context What the value is for. Valid values are view and edit
	 *
	 * @return mixed
	 */
	protected function getProp($prop, string $context = 'view')
	{
		$value = null;

		if(array_key_exists($prop, $this->data))
		{
			$value = array_key_exists($prop, $this->changes) ? $this->changes[$prop] : $this->data[$prop];

			if('view' === $context)
			{
				$value = apply_filters($this->getHookPrefix() . $prop, $value, $this);
			}
		}

		return $value;
	}

	/**
	 * Sets a date prop whilst handling formatting and datetime objects
	 *
	 * @param string $prop Name of prop to set
	 * @param string|integer $value Value of the prop
	 *
	 * @throws Exception|\Exception
	 */
	protected function setDateProp(string $prop, $value)
	{
		try
		{
			if(empty($value))
			{
				$this->setProp($prop, null);
				return;
			}

			if(is_a($value, 'Datetime'))
			{
				$datetime = $value;
			}
			elseif(is_numeric($value))
			{
				// Timestamps are handled as UTC timestamps in all cases
				$datetime = new Datetime("@{$value}", new DateTimeZone('UTC'));
			}
			else
			{
				// Strings are defined in local WP timezone. Convert to UTC
				if(1 === preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $value, $date_bits))
				{
					$offset = !empty($date_bits[7]) ? iso8601_timezone_to_offset($date_bits[7]) : $this->utilityTimezoneOffset();
					$timestamp = gmmktime($date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1]) - $offset;
				}
				else
				{
					$timestamp = $this->utilityStringToTimestamp(get_gmt_from_date(gmdate('Y-m-d H:i:s', $this->utilityStringToTimestamp($value))));
				}

				$datetime = new Datetime("@{$timestamp}", new DateTimeZone('UTC'));
			}

			// Set local timezone or offset
			if(get_option('timezone_string'))
			{
				$datetime->setTimezone(new DateTimeZone($this->utilityTimezoneString()));
			}
			else
			{
				$datetime->setUtcOffset($this->utilityTimezoneOffset());
			}

			$this->setProp($prop, $datetime);
		}
		catch(Exception $e){}
	}

	/**
	 * When invalid data is found, throw an exception unless reading from the DB
	 *
	 * @param string $code Error code
	 * @param string $message Error message
	 * @param int $http_status_code HTTP status code
	 *
	 * @throws Exception Data Exception
	 */
	protected function error(string $code, string $message, int $http_status_code = 400)
	{
		throw new Exception($code, $message, $http_status_code);
	}
}
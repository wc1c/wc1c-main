<?php namespace Wc1c\Cml\Abstracts;

use Wc1c\Cml\Contracts\DataContract;

/**
 * DataAbstract
 *
 * @package Wc1c\Cml
 */
abstract class DataAbstract implements DataContract
{
	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * @param array|null $data
	 */
	public function __construct($data = null)
	{
		if(null === $data)
		{
			return;
		}

		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}
}
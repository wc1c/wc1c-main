<?php namespace Wc1c\Cml\Entities;

use Wc1c\Cml\Abstracts\DataAbstract;
use Wc1c\Cml\Contracts\CounterpartyDataContract;

/**
 * Counterparty
 *
 * @package Wc1c\Cml
 */
class Counterparty extends DataAbstract implements CounterpartyDataContract
{
	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->data['id'];
	}

	/**
	 * @param string $id
	 */
	public function setId(string $id)
	{
		$this->data['id'] = $id;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->data['name'];
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name)
	{
		$this->data['name'] = $name;
	}
}
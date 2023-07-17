<?php namespace Wc1c\Cml\Contracts;

/**
 * CounterpartyDataContract
 *
 * @package Wc1c\Cml
 */
interface CounterpartyDataContract extends DataContract
{
	public function getId();

	public function getName();
}
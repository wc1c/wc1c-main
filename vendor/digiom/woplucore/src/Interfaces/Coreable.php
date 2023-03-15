<?php namespace Digiom\Woplucore\Interfaces;

/**
 * Interface Coreable
 *
 * @package Digiom\Woplucore\Interfaces
 */
interface Coreable
{
	/**
	 * Initialization
	 *
	 * @return void
	 */
	public function init();

	/**
	 * Admin initialization and chained
	 *
	 * @return void
	 */
	public function admin();

	/**
	 * Context chained
	 *
	 * @return Contextable
	 */
	public function context(): Contextable;

	/**
	 * Register in WordPress core
	 *
	 * @param Contextable $context
	 *
	 * @return mixed
	 */
	public function register(Contextable $context);
}
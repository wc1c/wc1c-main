<?php namespace Digiom\Woplucore\Interfaces;

/**
 * Interface Contextable
 *
 * @package Digiom\Woplucore\Interfaces
 */
interface Contextable
{
	/**
	 * @return string Unique plugin slug
	 */
	public function getSlug(): string;

	/**
	 * @return string Absolute path to the plugin main file.
	 */
	public function getFile(): string;

	/**
	 * @return Loadable Loader
	 */
	public function loader(): Loadable;
}
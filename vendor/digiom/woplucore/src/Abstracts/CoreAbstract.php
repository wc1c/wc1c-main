<?php namespace Digiom\Woplucore\Abstracts;

defined('ABSPATH') || exit;

use Digiom\Woplucore\Interfaces\Contextable;
use Digiom\Woplucore\Interfaces\Coreable;
use Digiom\Woplucore\Interfaces\Loadable;
use Digiom\Woplucore\Traits\SingletonTrait;

/**
 * CoreAbstract
 *
 * @package Digiom\Woplucore
 */
abstract class CoreAbstract implements Coreable
{
	use SingletonTrait;

	/**
	 * @var Loadable Loader
	 */
	protected $loader;

	/**
	 * @var Contextable Context
	 */
	protected $context;

	/**
	 * @param Contextable $context
	 *
	 * @return void
	 */
	public function register(Contextable $context)
	{
		if(has_filter($context->getSlug() . '_context_loading'))
		{
			$context = apply_filters($context->getSlug() . '_context_loading', $context);
		}

		$this->context = $context;

		// init
		add_action('init', [$this, 'init'], 3);

		// admin
		if($context->isAdmin())
		{
			add_action('init', [$this, 'admin'], 5);
		}
	}

	/**
	 * Initialization
	 */
	abstract public function init();

	/**
	 * Initialization and chained
	 */
	abstract public function admin();

	/**
	 * Loader
	 *
	 * @return Loadable
	 *
	 * @deprecated Use Core::instance()->context()->loader()
	 */
	public function loader(): Loadable
	{
		return $this->context()->loader();
	}

	/**
	 * Context
	 *
	 * @return Contextable
	 */
	public function context(): Contextable
	{
		return $this->context;
	}
}
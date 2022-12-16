<?php namespace Wc1c\Main\Tools;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Tools\Abstracts\ToolAbstract;
use Wc1c\Main\Traits\SingletonTrait;

/**
 * Core
 *
 * @package Wc1c\Main\Tools
 */
final class Core
{
	use SingletonTrait;

	/**
	 * @var array All loaded tools
	 */
	private $tools = [];

	/**
	 * Loading tools
	 *
	 * @return void
	 * @throws Exception
	 */
	public function load()
	{
		/**
		 * key = tool id
		 * value = callback - ToolAbstract
		 */
		$tools =
		[
			'environments' => Environments\Init::class
		];

		/**
		 * External tools loading is enabled
		 */
		if('yes' === wc1c()->settings()->get('extensions_tools', 'yes'))
		{
			$tools = apply_filters('wc1c_load_tools', $tools);
		}

		try
		{
			$this->set($tools);
		}
		catch(Exception $e)
		{
			throw new Exception('exception - ' . $e->getMessage());
		}
	}

	/**
	 * Get tools
	 *
	 * @param string $tool_id
	 *
	 * @return array|mixed
	 * @throws Exception
	 */
	public function get($tool_id = '')
	{
		if('' !== $tool_id)
		{
			if(array_key_exists($tool_id, $this->tools))
			{
				return $this->tools[$tool_id];
			}

			throw new Exception('$schema_id is unavailable');
		}

		return $this->tools;
	}

	/**
	 * Set tools
	 *
	 * @param array $tools
	 *
	 * @return void
	 * @throws Exception
	 */
	public function set($tools)
	{
		if(!is_array($tools))
		{
			throw new Exception('$tools is not valid');
		}

		$this->tools = $tools;
	}

	/**
	 * Initializing tools
	 *
	 * @param string $tool_id If a tool ID is specified, only the specified tool is loaded
	 *
	 * @return boolean|ToolAbstract
	 * @throws Exception
	 */
	public function init($tool_id = '')
	{
		try
		{
			$tools = $this->get();
		}
		catch(Exception $e)
		{
			throw new Exception('Get tools exception - ' . $e->getMessage());
		}

		if(!is_array($tools))
		{
			throw new Exception('$tools is not array');
		}

		/**
		 * Init specified tool
		 */
		if('' !== $tool_id)
		{
			if(!array_key_exists($tool_id, $tools))
			{
				throw new Exception('Tool not found by id: ' . $tool_id);
			}

			$init_tool = $tools[$tool_id];

			if(is_object($init_tool))
			{
				return $init_tool;
			}

			$init_tool = new $init_tool();

			if(!method_exists($init_tool, 'init'))
			{
				throw new Exception('Method init is not found');
			}

			try
			{
				$init_tool->init();
			}
			catch(Exception $e)
			{
				throw new Exception('Init tool exception - ' . $e->getMessage());
			}

			$tools[$tool_id] = $init_tool;

			$this->set($tools);

			return $init_tool;
		}

		/**
		 * Init all tools
		 */
		foreach($tools as $tool_unique_id => $tool_class)
		{
			try
			{
				$this->init($tool_unique_id);
			}
			catch(Exception $e)
			{
				wc1c()->log()->error($e->getMessage(), ['exception' => $e]);
				continue;
			}
		}

		return true;
	}
}
<?php namespace Wc1c\Main\Admin;

defined('ABSPATH') || exit;

use Wc1c\Main\Abstracts\ScreenAbstract;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Exceptions\RuntimeException;
use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Traits\UtilityTrait;

/**
 * Tools
 *
 * @package Wc1c\Main\Admin
 */
final class Tools extends ScreenAbstract
{
	use SingletonTrait;
	use UtilityTrait;

	/**
	 * @var array All available tools
	 */
	public $tools = [];

	/**
	 * @var string Current tool id
	 */
	private $current_tool_id = '';

	/**
	 * Tools constructor.
	 */
	public function __construct()
	{
		$this->init();
		parent::__construct();
	}

	/**
	 * Initialized
	 *
	 * @throws RuntimeException
	 */
	public function init()
	{
		try
		{
			$tools = wc1c()->tools()->get();
			$this->tools = $tools;
		}
		catch(Exception $exception){}

		$this->initCurrentId();
	}

	/**
	 * @return bool
	 */
	protected function initCurrentId()
	{
		$tool_id = sanitize_text_field(wc1c()->getVar($_GET['tool_id'], ''));

		if(!empty($tool_id) && array_key_exists($tool_id, $this->tools))
		{
			$this->setCurrentToolId($tool_id);
			return true;
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getCurrentToolId()
	{
		return $this->current_tool_id;
	}

	/**
	 * @param string $current_tool_id
	 */
	public function setCurrentToolId($current_tool_id)
	{
		$this->current_tool_id = $current_tool_id;
	}

	/**
	 * Output tools table
	 *
	 * @return void
	 */
	public function output()
	{
		if(empty($this->tools))
		{
			wc1c()->views()->getView('tools/empty.php');
			return;
		}

		if($this->getCurrentToolId() !== '' && $this->utilityIsWc1cAdminToolsRequest())
		{
			$tool = $this->tools[$this->getCurrentToolId()];

			$args =
			[
				'id' => $this->getCurrentToolId(),
				'name' => $tool->getName(),
				'description' => $tool->getDescription(),
				'back_url' => $this->utilityAdminToolsGetUrl(),
				'object' => $tool,
			];

			wc1c()->views()->getView('tools/single.php', $args);

			return;
		}

		$args['object'] = $this;

		wc1c()->views()->getView('tools/all.php', $args);
	}
}
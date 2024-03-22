<?php namespace Wc1c\Main\Admin\Configurations;

defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Traits\ProcessConfigurationTrait;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Traits\SingletonTrait;
use Wc1c\Main\Traits\UtilityTrait;

/**
 * Delete
 *
 * @package Wc1c\Main\Admin\Configurations
 */
class Delete
{
	use SingletonTrait;
	use ProcessConfigurationTrait;
	use UtilityTrait;

	/**
	 * Delete constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$configuration_id = absint(wc1c()->getVar($_GET['configuration_id'], 0));
		$error = $this->setConfiguration($configuration_id);

		if($error)
		{
			add_action('wc1c_admin_show', [$this, 'outputError'], 10);
		}
		else
		{
			$this->process();
		}
	}

	/**
	 * Delete processing
	 *
	 * @throws Exception
	 */
	public function process()
	{
		$configuration = $this->getConfiguration();

		$delete = false;
		$redirect = true;
		$force_delete = false;
		$configuration_status = $configuration->getStatus();

		$notice_args['type'] = 'error';
		$notice_args['data'] = __('Error. The configuration to be deleted is active and cannot be deleted.', 'wc1c-main');

		/**
		 * Защита от удаления активных соединений
		 */
		if(!$configuration->isStatus('active') && !$configuration->isStatus('processing'))
		{
			/**
			 * Окончательное удаление черновиков без корзины
			 */
			if($configuration_status === 'draft' && 'yes' === wc1c()->settings()->get('configurations_draft_delete', 'yes'))
			{
				$delete = true;
				$force_delete = true;
			}

			/**
			 * Помещение в корзину без удаления
			 */
			if($configuration_status !== 'deleted' && $force_delete === false)
			{
				$delete = true;
			}

			/**
			 * Окончательное удаление из корзины - вывод формы для подтверждения удаления
			 */
			if($configuration_status === 'deleted')
			{
				$redirect = false;
				$delete_form = new DeleteForm();

				if(!$delete_form->save())
				{
					add_action('wc1c_admin_configurations_form_delete_show', [$delete_form, 'output']);
					add_action('wc1c_admin_show', [$this, 'output'], 10);
				}
				else
				{
					$delete = true;
					$force_delete = true;
					$redirect = true;
				}
			}

			/**
			 * Удаление с переносом в список всех учетных записей и выводом уведомления об удалении
			 */
			if($delete)
			{
				$notice_args =
				[
					'type' => 'update',
					'data' => __('The configuration has been marked as deleted.', 'wc1c-main')
				];

				if($force_delete)
				{
					wc1c()->filesystem()->deleteDirectory($configuration->getUploadDirectory());

					$notice_args =
					[
						'type' => 'update',
						'data' => __('The configuration has been successfully deleted.', 'wc1c-main')
					];
				}

				if(!$configuration->delete($force_delete))
				{
					$notice_args['type'] = 'error';
					$notice_args['data'] = __('Configuration deleting error. Please retry again.', 'wc1c-main');
				}
			}
		}

		if($redirect)
		{
			wc1c()->admin()->notices()->create($notice_args);
			wp_safe_redirect($this->utilityAdminConfigurationsGetUrl());
			die;
		}
	}

	/**
	 * Output error
	 */
	public function outputError()
	{
		wc1c()->views()->getView('configurations/delete_error.php');
	}

	/**
	 * Output permanent remove
	 *
	 * @return void
	 */
	public function output()
	{
		$args['back_url'] = $this->utilityAdminConfigurationsGetUrl('all');
		$args['configuration'] = $this->getConfiguration();

		wc1c()->views()->getView('configurations/delete.php', $args);
	}
}
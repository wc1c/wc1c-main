<?php namespace Wc1c\Main\Admin\Configurations;

defined('ABSPATH') || exit;

use Wc1c\Main\Abstracts\FormAbstract;

/**
 * DeleteForm
 *
 * @package Wc1c\Main\Admin\Configurations
 */
class DeleteForm extends FormAbstract
{
	/**
	 * DeleteForm constructor.
	 */
	public function __construct()
	{
		$this->setId('configurations-delete');

		add_filter('wc1c_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_main'], 10);

		$this->loadFields();
	}

	/**
	 * Add for Main
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_main($fields)
	{
		$fields['accept'] =
		[
			'title' => __('Delete confirmation', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => sprintf(
				"%s<hr>%s",
				__('I confirm that Configuration will be permanently and irrevocably deleted from WooCommerce.', 'wc1c-main'),
				__('The directory with files for configuration from the FILE system will be completely removed.', 'wc1c-main')
			),
			'default' => 'no',
		];

		return $fields;
	}

	/**
	 * Form show
	 */
	public function output()
	{
		$args =
		[
			'object' => $this
		];

		wc1c()->views()->getView('configurations/delete_form.php', $args);
	}

	/**
	 * Save
	 *
	 * @return bool
	 */
	public function save()
	{
		$post_data = $this->getPostedData();

		if(!isset($post_data['_wc1c-admin-nonce-configurations-delete']))
		{
			return false;
		}

        $message = __('Configuration deleting error. Please retry.', 'wc1c-main');

		if(empty($post_data) || !wp_verify_nonce($post_data['_wc1c-admin-nonce-configurations-delete'], 'wc1c-admin-configurations-delete-save'))
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => $message
				]
			);

            wc1c()->log()->warning($message, ['user_id' => get_current_user_id(), 'form_id' => $this->getId()]);

			return false;
		}

		foreach($this->getFields() as $key => $field)
		{
			$field_type = $this->getFieldType($field);

			if('title' === $field_type || 'raw' === $field_type)
			{
				continue;
			}

			try
			{
				$this->saved_data[$key] = $this->getFieldValue($key, $field, $post_data);
			}
			catch(\Throwable $e)
			{
				wc1c()->admin()->notices()->create
				(
					[
						'type' => 'error',
						'data' => $e->getMessage()
					]
				);

				return false;
			}
		}

		$data = $this->getSavedData();

		if(!isset($data['accept']) || $data['accept'] !== 'yes')
		{
            $message = __('Configuration deleting error. Confirmation of final deletion is required.', 'wc1c-main');

			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => $message
				]
			);

            wc1c()->log()->warning($message, ['user_id' => get_current_user_id(), 'form_id' => $this->getId()]);

			return false;
		}

		return true;
	}
}
<?php namespace Wc1c\Main\Admin;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Abstracts\FormAbstract;

/**
 * InlineForm
 *
 * @package Wc1c\Main\Admin
 */
class InlineForm extends FormAbstract
{
	/**
	 * UpdateForm constructor.
	 */
	public function __construct($args = [])
	{
		$this->setId($args['id']);
		$this->loadFields($args['fields']);
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

		wc1c()->views()->getView('inline_form.php', $args);
	}

	/**
	 * Save
	 *
	 * @return array|boolean
	 */
	public function save()
	{
		$post_data = $this->getPostedData();

        $data_key = '_wc1c-admin-nonce-' . $this->getId();

		if(!isset($post_data[$data_key]))
		{
			return false;
		}

		if(empty($post_data) || !wp_verify_nonce($_POST[$data_key], 'wc1c-admin-' . $this->getId() . '-save'))
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Update error. Please retry.', 'wc1c-main')
				]
			);

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
			catch(Exception $e)
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

		return $this->getSavedData();
	}

	/**
	 * Generate Text Input HTML
	 *
	 * @param string $key - field key
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function generateTextHtml(string $key,array $data): string
	{
		$field_key = $this->getPrefixFieldKey($key);

		$defaults =
        [
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => [],
            'button_class' => ''
        ];

		$data = wp_parse_args($data, $defaults);

		ob_start();
		?>

		<div class="input-group">
			<input placeholder="<?php echo wp_kses_post( $data['title'] ); ?>" aria-label="<?php echo wp_kses_post( $data['title'] ); ?>" class="input-text fs-6 regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->getFieldData( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->getCustomAttributeHtml( $data ); ?>>
			<button type="submit" class="btn btn-outline-secondary <?php echo esc_attr( $data['button_class'] ); ?>"><?php echo wp_kses_post( $data['button'] ); ?></button>
		</div>
		<?php

		return ob_get_clean();
	}
}
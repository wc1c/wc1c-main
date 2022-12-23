<?php namespace Wc1c\Main\Admin\Configurations;

defined('ABSPATH') || exit;

use Wc1c\Main\Configuration;
use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Abstracts\FormAbstract;
use Wc1c\Main\Traits\UtilityTrait;

/**
 * CreateForm
 *
 * @package Wc1c\Main\Admin\Configurations
 */
class CreateForm extends FormAbstract
{
	use UtilityTrait;

	/**
	 * CreateForm constructor.
	 */
	public function __construct()
	{
		$this->set_id('configurations-create');

		add_filter('wc1c_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_main'], 10);

		$this->load_fields();
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
		$fields['name'] =
        [
            'title' => __('Configuration name', 'wc1c-main'),
            'type' => 'text',
            'description' => __('Enter any data up to 255 characters.', 'wc1c-main'),
            'default' => '',
            'css' => 'width: 100%;',
        ];

		try
		{
			$schemas = wc1c()->schemas()->get();
		}
		catch(Exception $e)
		{
			return $fields;
		}

		$options = [];
        $default_id = false;
		foreach($schemas as $schema_id => $schema_object)
		{
            if(false === $default_id)
            {
	            $default_id = $schema_id;
            }

			$options[$schema_id] = $schema_object->getName();
		}

		$fields['schema'] =
		[
			'title' => __('Configuration schema', 'wc1c-main'),
			'type' => 'radio',
			'description' => '',
			'default' => $default_id,
			'options' => $options,
			'class' => 'form-check-input',
			'class_label' => 'form-check-label fs-6 text-success',
		];

		return $fields;
	}

	/**
	 * Generate radio HTML
	 *
	 * @param string $key - field key
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function generate_radio_html($key, $data)
	{
		$field_key = $this->get_prefix_field_key($key);

		$defaults = array
		(
			'title' => '',
			'label' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => [],
			'options' => [],
		);

		$data = wp_parse_args($data, $defaults);

		if(!$data['label'])
		{
			$data['label'] = $data['title'];
		}

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html($data); ?></label>

				<div class="mt-2" style="font-weight: normal;">
					<?php echo wp_kses_post($this->get_description_html($data)); ?>
				</div>

			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>

					<?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>

					<div class="mb-3 border-1 border-light p-2" style="border: solid;">

                        <div>
	                        <?php _e('Identifier:', 'wc1c-main'); ?> <b><?php echo esc_attr($option_key); ?></b>
                            <hr>
                        </div>

						<input name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $option_key ); ?>" <?php disabled( $data['disabled'], true ); ?> class="<?php echo esc_attr( $data['class'] ); ?>" type="radio" value="<?php echo esc_attr($option_key); ?>" <?php checked( (string) $option_key, esc_attr( $this->get_field_data( $key ) ) ); ?> />

						<label class="<?php echo esc_attr( $data['class_label'] ); ?>" for="<?php echo esc_attr( $option_key ); ?>">
							<?php echo wp_kses_post($option_value); ?>
						</label>

						<div>
							<?php
								$schema = wc1c()->schemas()->get($option_key);
								echo wp_kses_post($schema->getDescription());
							?>
						</div>

					</div>

					<?php endforeach; ?>

				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Save
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function save()
	{
		$post_data = $this->get_posted_data();

		if(!isset($post_data['_wc1c-admin-nonce']))
		{
			return false;
		}

		if(empty($post_data) || !wp_verify_nonce($post_data['_wc1c-admin-nonce'], 'wc1c-admin-configurations-create-save'))
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Configuration create error. Please retry.', 'wc1c-main')
				]
			);

			return false;
		}

		foreach($this->get_fields() as $key => $field)
		{
			$field_type = $this->get_field_type($field);

			if('title' === $field_type || 'raw' === $field_type)
			{
				continue;
			}

			try
			{
				$this->saved_data[$key] = $this->get_field_value($key, $field, $post_data);
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
			}
		}

		$data = $this->get_saved_data();

		if(empty($data['name']))
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Configuration create error. Name is required.', 'wc1c-main')
				]
			);

			return false;
		}

		if(empty($data['schema']))
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Configuration create error. Schema is required.', 'wc1c-main')
				]
			);

			return false;
		}

		$configuration = new Configuration();
		$data_storage = $configuration->getStorage();
		$configuration->setStatus('draft');

		if('yes' === wc1c()->settings()->get('configurations_unique_name', 'yes') && $data_storage->isExistingByName($data['name']))
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Configuration create error. Name is exists.', 'wc1c-main')
				]
			);

			return false;
		}

		$configuration->setName($data['name']);
		$configuration->setSchema($data['schema']);
		$configuration->setStatus('draft');

		if($configuration->save())
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'update',
					'data' => __('Configuration create success. Configuration id: ', 'wc1c-main') . $configuration->getId()
					          . ' (<a href="' . $this->utilityAdminConfigurationsGetUrl('update', $configuration->getId()) . '">' . __('edit configuration', 'wc1c-main') . '</a>)'
				]
			);

			$this->set_saved_data([]);
			return true;
		}

		wc1c()->admin()->notices()->create
		(
			[
				'type' => 'error',
				'data' => __('Configuration create error. Please retry saving or change fields.', 'wc1c-main')
			]
		);

		return false;
	}

	/**
	 * Form show
	 */
	public function outputForm()
	{
		$args =
		[
			'object' => $this
		];

		wc1c()->views()->getView('configurations/create_form.php', $args);
	}
}
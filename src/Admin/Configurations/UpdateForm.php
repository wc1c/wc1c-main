<?php namespace Wc1c\Main\Admin\Configurations;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Abstracts\FormAbstract;
use Wc1c\Main\Traits\ConfigurationsUtilityTrait;

/**
 * UpdateForm
 *
 * @package Wc1c\Main\Admin\Configurations
 */
class UpdateForm extends FormAbstract
{
    use ConfigurationsUtilityTrait;

	/**
	 * UpdateForm constructor.
	 */
	public function __construct()
	{
		$this->setId('configurations-update');

		add_filter('wc1c_' . $this->getId() . '_form_load_fields', [$this, 'init_fields_main'], 3);
		add_action('wc1c_admin_configurations_update_sidebar_show', [$this, 'output_navigation'], 20);

		$this->loadFields();
	}

	/**
	 * Add for Main
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_main($fields): array
	{
		$options =
		[
			'active' => $this->utilityConfigurationsGetStatusesLabel('active'),
			'inactive' => $this->utilityConfigurationsGetStatusesLabel('inactive')
		];

		$fields['status'] =
		[
			'title' => __('Configuration status', 'wc1c-main'),
			'type' => 'select',
			'description' => __('Current configuration status.', 'wc1c-main'),
			'default' => 'inactive',
			'options' => $options
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

		wc1c()->views()->getView('configurations/update_form.php', $args);
	}

	/**
	 * Save
	 *
	 * @return array|boolean
	 */
	public function save()
	{
		$post_data = $this->getPostedData();

		if(!isset($post_data['_wc1c-admin-nonce']))
		{
			return false;
		}

		if(empty($post_data) || !wp_verify_nonce($post_data['_wc1c-admin-nonce'], 'wc1c-admin-configurations-update-save'))
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
	 * Navigation show
	 */
	public function output_navigation()
	{
        $show = false;

		$args =
        [
            'header' => '<h3 class="p-0 m-0">' . __('Fast navigation', 'wc1c-main') . '</h3>',
            'object' => $this
        ];

		$body = '<div class="wc1c-toc m-0">';

		$form_fields = $this->getFields();

		foreach($form_fields as $k => $v)
		{
			$type = $this->getFieldType($v);

			if($type !== 'title')
			{
				continue;
			}

			if(method_exists($this, 'generate_navigation_html'))
			{
                $show = true;
				$body .= $this->{'generate_navigation_html'}($k, $v);
			}
		}

		$body .= '</div>';

        if($show)
        {
	        $args['body'] = $body;

	        wc1c()->views()->getView('configurations/update_sidebar_toc.php', $args);
        }
	}

	/**
	 * Generate navigation HTML
	 *
	 * @param string $key - field key
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function generate_navigation_html($key, $data)
	{
		$field_key = $this->getPrefixFieldKey($key);

		$defaults =
		[
			'title' => '',
			'class' => '',
		];

		$data = wp_parse_args($data, $defaults);

		ob_start();
		?>
		<a class="list-group-item m-0 border-0" href="#<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?></a>
		<?php

		return ob_get_clean();
	}
}
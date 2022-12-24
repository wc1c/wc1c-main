<?php namespace Wc1c\Main\Admin\Settings;

defined('ABSPATH') || exit;

use Wc1c\Main\Exceptions\Exception;
use Wc1c\Main\Settings\MainSettings;

/**
 * MainForm
 *
 * @package Wc1c\Main\Admin
 */
class MainForm extends Form
{
	/**
	 * MainForm constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->set_id('settings-main');
		$this->setSettings(new MainSettings());

		add_filter('wc1c_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_main'], 10);
		add_filter('wc1c_' . $this->get_id() . '_form_load_fields', [$this, 'init_form_fields_tecodes'], 10);

		add_filter('wc1c_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_configurations'], 20);
		add_filter('wc1c_' . $this->get_id() . '_form_load_fields', [$this, 'init_fields_technical'], 30);

		$this->init();
	}

	/**
	 * Add fields for Configurations
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_configurations($fields): array
	{
		$fields['configurations_title'] =
		[
			'title' => __('Configurations', 'wc1c-main'),
			'type' => 'title',
			'description' => __('Some settings for the configurations.', 'wc1c-main'),
		];

		$fields['configurations_unique_name'] =
		[
			'title' => __('Unique names', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Require unique names for configurations?', 'wc1c-main'),
			'description' => __('If enabled, will need to provide unique names for the configurations.', 'wc1c-main'),
			'default' => 'yes'
		];

		$fields['configurations_show_per_page'] =
		[
			'title' => __('Number in the list', 'wc1c-main'),
			'type' => 'text',
			'description' => __('The number of displayed configurations on one page.', 'wc1c-main'),
			'default' => 10,
			'css' => 'min-width: 20px;',
		];

		$fields['configurations_draft_delete'] =
		[
			'title' => __('Deleting drafts without trash', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Enable deleting drafts without placing them in the trash?', 'wc1c-main'),
			'description' => __('If enabled, configurations for connections in the draft status will be deleted without being added to the basket.', 'wc1c-main'),
			'default' => 'yes'
		];

		return $fields;
	}

	/**
	 * Add for Technical
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_fields_technical($fields): array
	{
		$fields['technical_title'] =
		[
			'title' => __('Technical settings', 'wc1c-main'),
			'type' => 'title',
			'description' => __('Used to set up the environment.', 'wc1c-main'),
		];

		$fields['php_max_execution_time'] =
		[
			'title' => __('Maximum time for execution PHP', 'wc1c-main'),
			'type' => 'text',
			'description' => sprintf
			(
				'%s <br /> %s <b>%s</b> <br /> %s',
				__('Value is seconds. WC1C will run until a time limit is set.', 'wc1c-main'),
				__('Server value:', 'wc1c-main'),
				wc1c()->environment()->get('php_max_execution_time'),
				__('If specify 0, the time limit will be disabled. Specifying 0 is not recommended, it is recommended not to exceed the server limit.', 'wc1c-main')
			),
			'default' => wc1c()->environment()->get('php_max_execution_time'),
			'css' => 'min-width: 100px;',
		];

		$fields['php_post_max_size'] =
		[
			'title' => __('Maximum request size', 'wc1c-main'),
			'type' => 'text',
			'description' => __('The setting must not take a size larger than specified in the server settings.', 'wc1c-main'),
			'default' => wc1c()->environment()->get('php_post_max_size'),
			'css' => 'min-width: 100px;',
		];

		return $fields;
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
		$fields['receiver'] =
		[
			'title' => __('Receiver', 'wc1c-main'),
			'type' => 'checkbox',
			'label' => __('Enable data Receiver: background requests?', 'wc1c-main'),
			'description' => __('It is used to receive background requests from 1C in exchange schemes. Do not disable this option if you do not know what it is for.', 'wc1c-main'),
			'default' => 'yes'
		];

		return $fields;
	}

	/**
	 * Add fields for tecodes
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	public function init_form_fields_tecodes($fields): array
	{
		$buy_url = esc_url('https://wc1c.info/market/code');

		$fields['tecodes'] =
		[
			'title' => __('Activation', 'wc1c-main'),
			'type' => 'title',
			'description' => sprintf
            (
                '%s <a target="_blank" href="%s">%s</a>.',
                __('The code can be obtained from the plugin website:', 'wc1c-main'),
                $buy_url,
                $buy_url
            ),
        ];

		if(wc1c()->tecodes()->is_valid())
		{
			$fields['tecodes_status'] =
            [
                'title' => __('Status', 'wc1c-main'),
                'type' => 'tecodes_status',
                'class' => 'p-2',
                'description' => __('Code activated. To activate another code, you can enter it again.', 'wc1c-main'),
                'default' => ''
            ];
		}

        $fields['tecodes_code'] =
        [
            'title' => __('Code for activation', 'wc1c-main'),
            'type' => 'tecodes_text',
            'class' => 'p-2',
            'description' => sprintf
            (
                '%s <b>%s</b><br /> <hr> %s <b>%s</b>',
                __('If enter the correct code, the current environment will be activated.', 'wc1c-main'),
                __('Enter the code only on the actual workstation.', 'wc1c-main'),
                __('Current activation API status:', 'wc1c-main'),
                esc_attr__(wc1c()->tecodes()->api_get_status(), 'wc1c-main')
            ),
            'default' => ''
        ];

		return $fields;
	}

	/**
	 * Generate Tecodes data HTML
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 *
	 * @return string
	 */
	public function generate_tecodes_status_html(string $key, array $data): string
	{
		$field_key = $this->get_prefix_field_key($key);
		$defaults = array
		(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => [],
		);

		$data = wp_parse_args($data, $defaults);

		$local = wc1c()->tecodes()->get_local_code();
		$local_data = wc1c()->tecodes()->get_local_code_data($local);

		ob_start();

		?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); ?></label>
            </th>
            <td class="forminp">
                <div class="wc1c-custom-metas">

		            <?php

                        if($local_data['code_date_expires'] === 'never')
                        {
                            $local_data['code_date_expires'] = __('never', 'wc1c-main');
                        }
                        else
                        {
	                        $local_data['code_date_expires'] = date_i18n(get_option('date_format'), $local_data['code_date_expires']);
                        }

                        printf
                        (
                                '%s: <b>%s</b> (%s %s)<br />%s: <b>%s</b><br />%s: <b>%s</b>',
                                __('Code ID', 'wc1c-main'),
                                $local_data['code_id'],
                                __('expires:', 'wc1c-main'),
                                $local_data['code_date_expires'] ,
                                __('Instance ID', 'wc1c-main'),
                                $local_data['instance_id'],
                                __('Domain', 'wc1c-main'),
                                $local_data['instance']['domain']
                        );
		            ?>

                </div>
				<?php echo $this->get_description_html($data); // WPCS: XSS ok.?>
            </td>
        </tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Tecodes Text Input HTML
	 *
	 * @param string $key Field key.
	 * @param array $data Field data.
	 *
	 * @return string
	 */
	public function generate_tecodes_text_html(string $key, array $data): string
	{
		$field_key = $this->get_prefix_field_key($key);
		$defaults = array
		(
			'title' => '',
			'disabled' => false,
			'class' => '',
			'css' => '',
			'placeholder' => '',
			'type' => 'text',
			'desc_tip' => false,
			'description' => '',
			'custom_attributes' => [],
		);

		$data = wp_parse_args($data, $defaults);

		ob_start();
		?>
		<tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); ?></label>
            </th>
			<td class="forminp">
                <div class="input-group">
                    <input class="form-control input-text regular-input <?php echo esc_attr($data['class']); ?>"
                    type="<?php echo esc_attr($data['type']); ?>" name="<?php echo esc_attr($field_key); ?>"
                    id="<?php echo esc_attr($field_key); ?>" style="<?php echo esc_attr($data['css']); ?>"
                    value="<?php echo esc_attr($this->get_field_data($key)); ?>"
                    placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo $this->get_custom_attribute_html($data); // WPCS: XSS ok.
                    ?> />
                    <button name="save" class="btn btn-primary" type="submit" value="<?php _e('Activate', 'wc1c-main') ?>"><?php _e('Activate', 'wc1c-main') ?></button>
                </div>
                <?php echo $this->get_description_html($data); // WPCS: XSS ok.?>
            </td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Validate tecodes code
     *
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function validate_tecodes_code_field(string $key, string $value): string
	{
		if($value === '')
		{
			return '';
		}

		$value_valid = explode('-', $value);
		if('WPWC1C' !== strtoupper(reset($value_valid)) && 'WC1C' !== strtoupper(reset($value_valid)))
		{
			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('The code is invalid. Enter the correct code.', 'wc1c-main')
				]
			);
			return '';
		}

		wc1c()->tecodes()->delete_local_code();
		wc1c()->tecodes()->set_code($value);

		if(false === wc1c()->tecodes()->validate())
		{
			$errors = wc1c()->tecodes()->get_errors();

			if(is_array($errors))
			{
				foreach(wc1c()->tecodes()->get_errors() as $error_key => $error)
				{
					wc1c()->admin()->notices()->create
					(
						[
							'type' => 'error',
							'data' => $error
						]
					);
				}
			}
		}
        else
        {
	        wc1c()->admin()->notices()->create
	        (
		        [
			        'type' => 'info',
			        'data' => __('Code activated successfully. Reload the page to display.', ('wc1c-main'))
		        ]
	        );
        }

		return '';
	}
}
<?php namespace Wc1c\Main\Abstracts;

defined('ABSPATH') || exit;

/**
 * FormAbstract
 *
 * @package Wc1c\Main\Abstracts
 */
abstract class FormAbstract
{
	/**
	 * @var string Form id
	 */
	protected $id = '';

	/**
	 * @var bool
	 */
	public $title_numeric = false;

	/**
	 * @var array Form validation messages
	 */
	protected $messages = [];

	/**
	 * @var array Form fields
	 */
	protected $fields = [];

	/**
	 * @var array The posted data
	 */
	protected $posted_data = [];

	/**
	 * @var array Saved form data
	 */
	protected $saved_data = [];

	/**
	 * Get form id
	 *
	 * @return string
	 */
	public function get_id()
	{
		return $this->id;
	}

	/**
	 * Set form id
	 *
	 * @param string $id
	 */
	public function set_id($id)
	{
		$this->id = $id;
	}

	/**
	 * Get form fields
	 *
	 * @return array
	 */
	public function get_fields()
	{
		return $this->fields;
	}

	/**
	 * Set form fields
	 *
	 * @param array $fields
	 */
	public function set_fields($fields)
	{
		$this->fields = $fields;
	}

	/**
	 * Get saved data
	 *
	 * @return array
	 */
	public function get_saved_data()
	{
		return $this->saved_data;
	}

	/**
	 * Set saved data
	 *
	 * @param array $saved_data
	 */
	public function set_saved_data($saved_data)
	{
		$this->saved_data = $saved_data;
	}

	/**
	 * Loading saved data
	 *
	 * @param array $saved_data
	 */
	public function load_saved_data($saved_data = [])
	{
		$saved_data = apply_filters('wc1c_' . $this->get_id() . '_form_load_saved_data', $saved_data);
		$this->set_saved_data($saved_data);
	}

	/**
	 * Loading form fields
	 *
	 * @param array $fields
	 */
	public function load_fields($fields = [])
	{
		$fields = apply_filters('wc1c_' . $this->get_id() . '_form_load_fields', $fields);
		$this->set_fields($fields);
	}

	/**
	 * Prefix key for form field
	 *
	 * @param string $key - field key
	 *
	 * @return string
	 */
	public function get_prefix_field_key($key)
	{
		return 'wc1c_' . $this->get_id() . '_form_field_' . $key;
	}

	/**
	 * Get field data
	 * An field data from the form, using defaults if necessary to prevent undefined notices
	 *
	 * @param string $key - field key
	 * @param mixed $empty_value - value when empty
	 *
	 * @return string - the value specified for the field or a default value for the field
	 */
	public function get_field_data($key, $empty_value = null)
	{
		if(!isset($this->saved_data[$key]))
		{
			$form_fields = $this->get_fields();

			$this->saved_data[$key] = isset($form_fields[$key]) ? $this->get_field_default($form_fields[$key]) : '';
		}

		if(!is_null($empty_value) && '' === $this->saved_data[$key])
		{
			$this->saved_data[$key] = $empty_value;
		}

		return $this->saved_data[$key];
	}

	/**
	 * Output
	 */
	public function outputForm()
	{
		echo '<table id="' . esc_attr($this->get_id()) . '" class="form-table">' . $this->generate_html($this->get_fields(), false) . '</table>';
	}

	/**
	 * Sets the POSTed data
	 * This method can be used to set specific data, instead of taking it from the $_POST array
	 *
	 * @param array $data - posted data
	 */
	public function set_posted_data($data = [])
	{
		$this->posted_data = $data;
	}

	/**
	 * Returns the POSTed data
	 * Used to save the form
	 *
	 * @return array
	 */
	public function get_posted_data()
	{
		if(!empty($this->posted_data) && is_array($this->posted_data))
		{
			return $this->posted_data;
		}

		return $_POST;
	}

	/**
	 * Generate HTML
	 *
	 * @param array $form_fields (default: array()) Array of form fields
	 * @param bool $echo - echo or return
	 *
	 * @return string|void The html for the form
	 */
	public function generate_html($form_fields = [], $echo = true)
	{
		if(empty($form_fields))
		{
			$form_fields = $this->get_fields();
		}

		$html = '';

		$i = 1;
		$g = 1;

		foreach($form_fields as $k => $v)
		{
			$type = $this->get_field_type($v);

			if($this->title_numeric)
			{
				if($type === 'title')
				{
					$i++;
					$g = 1;
				}
				else
				{
					$v['title'] = $i . '.' . $g .') ' . $v['title'];
					$g++;
				}
			}

			if(method_exists($this, 'generate_' . $type . '_html'))
			{
				$html .= $this->{'generate_' . $type . '_html'}($k, $v);

				continue;
			}

			$html .= $this->generate_text_html($k, $v);
		}

		if($echo !== true)
		{
			return $html;
		}

		echo $html;
	}

	/**
	 * Get HTML for tooltips
	 *
	 * @param array $data Data for the tooltip
	 *
	 * @return string
	 */
	public function get_tooltip_html($data)
	{
		if(true === $data['desc_tip'])
		{
			$tooltip = $data['description'];
		}
		elseif(!empty($data['desc_tip']))
		{
			$tooltip = $data['desc_tip'];
		}
		else
		{
			$tooltip = '';
		}

		return $tooltip ? $this->help_tooltip($tooltip, true) : '';
	}

	/**
	 * Display help tooltip
	 *
	 * @param string $tooltip - help tooltip text
	 * @param bool $allow_html - allow sanitized HTML if true or escape
	 *
	 * @return string
	 */
	public function help_tooltip($tooltip, $allow_html = false)
	{
		if($allow_html)
		{
			$tooltip = $this->sanitize_tooltip($tooltip);
		}
		else
		{
			$tooltip = esc_attr($tooltip);
		}

		return '<span class="woocommerce-help-tip" data-tip="' . $tooltip . '"></span>';
	}

	/**
	 * Sanitize a string destined to be a tooltip
	 *
	 * @param string $var
	 *
	 * @return string
	 */
	public function sanitize_tooltip($var)
	{
		return htmlspecialchars(wp_kses(html_entity_decode($var), array
		(
			'br' => [],
			'em' => [],
			'strong' => [],
			'small' => [],
			'span' => [],
			'ul' => [],
			'li' => [],
			'ol' => [],
			'p' => [],
		)));
	}

	/**
	 * Get HTML for descriptions
	 *
	 * @param array $data - data for the description
	 *
	 * @return string
	 */
	public function get_description_html($data)
	{
		if(true === $data['desc_tip'])
		{
			$description = '';
		}
		elseif(!empty($data['desc_tip']))
		{
			$description = $data['description'];
		}
		elseif(!empty($data['description']))
		{
			$description = $data['description'];
		}
		else
		{
			$description = '';
		}

		return $description ? '<p class="description">' . wp_kses_post($description) . '</p>' . "\n" : '';
	}

	/**
	 * Get custom attributes
	 *
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function get_custom_attribute_html($data)
	{
		$custom_attributes = [];

		if(!empty($data['custom_attributes']) && is_array($data['custom_attributes']))
		{
			foreach($data['custom_attributes'] as $attribute => $attribute_value)
			{
				$custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
			}
		}

		return implode(' ', $custom_attributes);
	}

	/**
	 * Generate Text Input HTML
	 *
	 * @param string $key - field key
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function generate_text_html($key, $data)
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
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_field_data( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?> />
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Password Input HTML
	 *
	 * @param string $key - field key
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function generate_password_html($key, $data)
	{
		$data['type'] = 'password';
		return $this->generate_text_html($key, $data);
	}

	/**
	 * Generate RAW HTML
	 *
	 * @param string $key Field key
	 * @param array $data Field data
	 *
	 * @return string
	 */
	public function generate_raw_html($key, $data)
	{
		$field_key = $this->get_prefix_field_key($key);

		$defaults = array
		(
			'title' => '',
			'type' => 'raw',
			'desc_tip' => false,
			'description' => '',
		);

		$data = wp_parse_args($data, $defaults);

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<?php echo wp_kses_post($data['raw']); ?>
					<?php echo $this->get_description_html($data); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Textarea HTML
	 *
	 * @param string $key Field key
	 * @param array $data Field data
	 *
	 * @return string
	 */
	public function generate_textarea_html($key, $data)
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

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<textarea rows="3" cols="20" class="input-text wide-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo esc_textarea( $this->get_field_data( $key ) ); ?></textarea>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate checkbox HTML
	 *
	 * @param string $key - field key
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function generate_checkbox_html($key, $data)
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
			'custom_attributes' => array(),
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
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<label for="<?php echo esc_attr( $field_key ); ?>">
						<input <?php disabled( $data['disabled'], true ); ?> class="<?php echo esc_attr( $data['class'] ); ?>" type="checkbox" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="1" <?php checked( $this->get_field_data( $key ), 'yes' ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?> /> <?php echo wp_kses_post( $data['label'] ); ?></label><br/>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
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
			'type' => 'radio',
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
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>

	                    <?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>
                            <input name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $option_key ); ?>" <?php disabled( $data['disabled'], true ); ?> class="<?php echo esc_attr( $data['class'] ); ?>" type="radio" value="<?php echo esc_attr($option_key); ?>" <?php checked( (string) $option_key, esc_attr( $this->get_field_data( $key ) ) ); ?> />

                            <label class="<?php echo esc_attr( $data['class_label'] ); ?>" for="<?php echo esc_attr( $option_key ); ?>">
	                             <?php echo wp_kses_post($option_value); ?>
                            </label>
                            <br/>
                        <?php endforeach; ?>

                    <br/>
                    <?php echo $this->get_description_html($data); ?>
                </fieldset>
            </td>
        </tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate Select HTML
	 *
	 * @param string $key - field key
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function generate_select_html($key, $data)
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
			'options' => [],
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<select class="select <?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); // WPCS: XSS ok. ?>>
						<?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>
							<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( (string) $option_key, esc_attr( $this->get_field_data( $key ) ) ); ?>><?php echo esc_attr( $option_value ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate multiselect HTML
	 *
	 * @param string $key - field key
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function generate_multiselect_html($key, $data)
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
			'select_buttons' => false,
			'options' => [],
		);

		$data = wp_parse_args($data, $defaults);
		$value = (array) $this->get_field_data($key, []);

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<select multiple="multiple" class="multiselect <?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>[]" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); // WPCS: XSS ok. ?>>
						<?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>
							<?php if ( is_array( $option_value ) ) : ?>
								<optgroup label="<?php echo esc_attr( $option_key ); ?>">
									<?php foreach ( $option_value as $option_key_inner => $option_value_inner ) : ?>
										<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( in_array( (string) $option_key_inner, $value, true ), true ); ?>><?php echo esc_attr( $option_value_inner ); ?></option>
									<?php endforeach; ?>
								</optgroup>
							<?php else : ?>
								<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( in_array( (string) $option_key, $value, true ), true ); ?>><?php echo esc_attr( $option_value ); ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
					<?php echo $this->get_description_html( $data ); ?>
					<?php if ( $data['select_buttons'] ) : ?>
						<br/><a class="select_all button" href="#"><?php esc_html_e( 'Select all', 'woocommerce' ); ?></a> <a class="select_none button" href="#"><?php esc_html_e( 'Select none', 'woocommerce' ); ?></a>
					<?php endif; ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Generate title HTML
	 *
	 * @param string $key - field key
	 * @param array $data - field data
	 *
	 * @return string
	 */
	public function generate_title_html($key, $data)
	{
		$field_key = $this->get_prefix_field_key($key);

		$defaults = array
		(
			'title' => '',
			'class' => '',
		);

		$data = wp_parse_args($data, $defaults);

		ob_start();
		?>
		</table><a class="nav-link d-none" href="#<?php echo esc_attr($field_key); ?>"></a>
		<div class="wc1c-title-wrap">
			<h3 class="wc-settings-sub-title <?php echo esc_attr($data['class']); ?>" id="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?></h3>
			<?php if (!empty($data['description'])) : ?>
				<p><?php echo wp_kses_post($data['description']); ?></p>
			<?php endif; ?>
		</div><table class="form-table wc1c-form-table">
		<?php

		return ob_get_clean();
	}

	/**
	 * Validate password field
	 * No input sanitization is used to avoid corrupting passwords
	 *
	 * @param string $key - field key
	 * @param string $value - posted Value
	 *
	 * @return string
	 */
	public function validate_password_field($key, $value)
	{
		$value = is_null($value) ? '' : $value;

		return trim(stripslashes($value));
	}

	/**
	 * Validate text field
	 * Make sure the data is escaped correctly, etc
	 *
	 * @param string $key - field key
	 * @param string $value - posted Value
	 *
	 * @return string
	 */
	public function validate_text_field($key, $value)
	{
		$value = is_null($value) ? '' : $value;

		return wp_kses_post(trim(stripslashes($value)));
	}

	/**
	 * Validate textarea field
	 *
	 * @param string $key - field key
	 * @param string $value - posted value
	 *
	 * @return string
	 */
	public function validate_textarea_field($key, $value)
	{
		$value = is_null($value) ? '' : $value;

		return wp_kses(trim(stripslashes($value)),
		       array_merge
		       (
		           ['iframe' =>
			           [
			               'src' => true,
			               'style' => true,
			               'id' => true,
			               'class' => true,
		               ],
		           ],
		           wp_kses_allowed_html('post')
		       )
		);
	}

	/**
	 * Validate checkbox field
	 * If not set, return "no", otherwise return "yes"
	 *
	 * @param string $key - field key
	 * @param string $value - posted Value
	 *
	 * @return string
	 */
	public function validate_checkbox_field($key, $value)
	{
		return !is_null($value) ? 'yes' : 'no';
	}

	/**
	 * Validate select field
	 *
	 * @param string $key - field key
	 * @param string $value - posted Value
	 *
	 * @return string
	 */
	public function validate_select_field($key, $value)
	{
		$value = is_null($value) ? '' : $value;

		return $this->clean(stripslashes($value));
	}

	/**
	 * Clean variables using sanitize_text_field
	 * Arrays are cleaned recursively
	 * Non-scalar values are ignored
	 *
	 * @param string|array $var
	 *
	 * @return string|array
	 */
	public function clean($var)
	{
		if(is_array($var))
		{
			return array_map(array($this, 'clean'), $var);
		}

		return is_scalar($var) ? sanitize_text_field($var) : $var;
	}

	/**
	 * Run clean over posted textarea but maintain line breaks
	 *
	 * @param string $var
	 *
	 * @return string
	 */
	public function sanitize_textarea($var)
	{
		return implode("\n", array_map(array($this, 'clean'), explode("\n", $var)));
	}

	/**
	 * Validate multiselect field
	 *
	 * @param string $key - field key
	 * @param string|array $value - posted Value
	 *
	 * @return string|array
	 */
	public function validate_multiselect_field($key, $value)
	{
		return is_array($value) ? array_map(array($this, 'clean'), array_map('stripslashes', $value)) : '';
	}

	/**
	 * Add a message for display in admin on save
	 *
	 * @param $type
	 * @param $message - message
	 */
	public function add_message($type, $message)
	{
		$this->messages[] = array
		(
			'type' => $type,
			'message' => $message
		);
	}

	/**
	 * Get all messages
	 */
	public function get_messages()
	{
		return $this->messages;
	}

	/**
	 * Get a fields type
	 * Defaults to "text" if not set
	 *
	 * @param array $field - field key
	 *
	 * @return string
	 */
	public function get_field_type($field)
	{
		return empty($field['type']) ? 'text' : $field['type'];
	}

	/**
	 * Get a fields default value.
	 * Defaults to "" if not set
	 *
	 * @param array $field - field key
	 *
	 * @return string
	 */
	public function get_field_default($field)
	{
		return empty($field['default']) ? '' : $field['default'];
	}

	/**
	 * Get a field's posted and validated value
	 *
	 * @param string $key - field key
	 * @param array $field - field array
	 * @param array $post_data - posted data
	 *
	 * @return string
	 */
	public function get_field_value($key, $field, $post_data = array())
	{
		$type = $this->get_field_type($field);
		$field_key = $this->get_prefix_field_key($key);

		$post_data = empty($post_data) ? $_POST : $post_data;
		$value = isset($post_data[$field_key]) ? $post_data[$field_key] : null;

		if(isset($field['sanitize_callback']) && is_callable($field['sanitize_callback']))
		{
			return call_user_func($field['sanitize_callback'], $value);
		}

		if(is_callable(array($this, 'validate_' . $key . '_field')))
		{
			return $this->{'validate_' . $key . '_field'}($key, $value);
		}

		if(is_callable(array($this, 'validate_' . $type . '_field')))
		{
			return $this->{'validate_' . $type . '_field'}($key, $value);
		}

		return $this->validate_text_field($key, $value);
	}
}
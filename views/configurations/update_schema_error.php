<?php defined('ABSPATH') || exit;

    $title = __('Warning', 'wc1c-main');
    $title = apply_filters('wc1c_admin_configurations_update_schema_error_title', $title);

    $text = __('Update is not available.', 'wc1c-main');
    $text = apply_filters('wc1c_admin_configurations_update_schema_error_text', $text);

?>

<div class="wc1c-configurations-alert mb-2">
    <h3><?php printf('%s', sanitize_text_field($title)); ?></h3>
    <p class="fs-6"><?php printf('%s', sanitize_text_field($text)); ?></p>
</div>
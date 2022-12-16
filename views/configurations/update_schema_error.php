<?php defined('ABSPATH') || exit; ?>

<?php
$title = __('Warning', 'wc1c-main');
$title = apply_filters('wc1c_admin_configurations_update_schema_error_title', $title);
$text = __('Update is not available.', 'wc1c-main');
$text = apply_filters('wc1c_admin_configurations_update_schema_error_text', $text);
?>

<div class="wc1c-configurations-alert mb-2">
    <h3><?php echo $title; ?></h3>
    <p><?php echo $text; ?></p>
</div>
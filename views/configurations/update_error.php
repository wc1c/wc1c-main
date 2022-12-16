<?php defined('ABSPATH') || exit;

    $label = __('Back to configurations list', 'wc1c-main');
    wc1c()->views()->adminBackLink($label, $args['back_url']);
?>

<?php
$title = __('Error', 'wc1c-main');
$title = apply_filters('wc1c_admin_configurations_update_error_title', $title);
$text = __('Update is not available. Configuration not found or unavailable.', 'wc1c-main');
$text = apply_filters('wc1c_admin_configurations_update_error_text', $text);
?>

<div class="wc1c-configurations-alert mb-2 mt-2">
    <h3><?php echo $title; ?></h3>
    <p><?php echo $text; ?></p>
</div>
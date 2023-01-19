<?php defined('ABSPATH') || exit; ?>

<?php
    $title = __('Connection', 'wc1c-main');

    if(has_filter('wc1c_admin_settings_connect_title'))
    {
        $title = apply_filters('wc1c_admin_settings_connect_title', $title);
    }

    $text = sprintf
    (
        '<p>%s</p> %s',
        __('To receive official services and improve of the plugin, you must go through the authorization of an external application.', 'wc1c-main'),
        __('Authorization of an external app occurs by going to the official WC1C and returning to the current site.', 'wc1c-main')
    );

    if(has_filter('wc1c_admin_settings_connect_text'))
    {
        $text = apply_filters('wc1c_admin_settings_connect_text', $text);
    }
?>

<div class="wc1c-configurations-alert mb-2 mt-2">
    <h3><?php esc_html_e($title); ?></h3>
    <p><?php echo wp_kses_post($text); ?></p>
</div>
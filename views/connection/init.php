<?php defined('ABSPATH') || exit; ?>

<?php
    $title = __('Connection', 'wc1c-main');
    $title = apply_filters('wc1c_admin_settings_connect_title', $title);

    $text = sprintf
    (
            '<p>%s</p> %s',
            __('To receive support and official services, need to go through the authorization of external applications.', 'wc1c-main'),
            __('Authorization of an external app occurs by going to the official WC1C and returning to the current site.', 'wc1c-main')
    );

    $text = apply_filters('wc1c_admin_settings_connect_text', $text);
?>

<div class="wc1c-configurations-alert mb-2 mt-2">
    <h3><?php esc_html_e($title); ?></h3>
    <p><?php esc_html_e($text); ?></p>
</div>
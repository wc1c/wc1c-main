<?php defined('ABSPATH') || exit;

$text = sprintf
(
    '%s %s<hr>%s',
    __('Viewing logs is possible with the extension installed. The logs contain operation information and error information.', 'wc1c-main'),
    __('If something does not work, or it is not clear how it works, look at the logs. Without a log viewer extension, they can be viewed via FTP.', 'wc1c-main'),
    __('After installing the extension, this section will be filled with extension features for viewing logs.', 'wc1c-main')
);

$img = wc1c()->environment()->get('plugin_directory_url') . 'assets/images/promo_logs.png';

?>

<div class="bg-white p-2 rounded">
    <div class="alert wc1c-configurations-alert mb-3 mt-1 bg-light">
        <p class="fs-6"><?php echo wp_kses_post($text); ?></p>
    </div>

    <img src="<?php echo esc_url($img); ?>" class="card-img" alt="Logs">
</div>

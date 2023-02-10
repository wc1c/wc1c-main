<?php defined('ABSPATH') || exit;

$text = sprintf
(
    '%s %s<hr>%s',
    __('Your copy of the free software has not been activated.', 'wc1c-main'),
    __('We recommend that you activate your copy of the free software for stable updates and better performance.', 'wc1c-main'),
    __('After activation, this section will disappear and will no longer be shown.', 'wc1c-main')
);
?>

<div class="alert wc1c-configurations-alert mb-2 mt-2">
    <p class="fs-6"><?php echo wp_kses_post($text); ?></p>
</div>

<div class="">
    <h2><?php _e('How to activate?', 'wc1c-main'); ?></h2>
    <ul>
        <li class="fs-6"><b>1.</b> <?php _e('Get an activation code in any available way. For example, on the official website.', 'wc1c-main'); ?></li>
        <li class="fs-6"><b>2.</b> <?php _e('Enter the activation code in the plugin settings.', 'wc1c-main'); ?> (<a href="<?php printf('%s', get_home_url('', add_query_arg(['section' => 'settings', 'do_settings' => 'activation']))) ?>"><?php printf('%s', get_home_url('', add_query_arg(['section' => 'settings', 'do_settings' => 'activation']))) ?></a>)</li>
    </ul>
</div>

<div class="">
    <h2><?php _e('Why is activation required?', 'wc1c-main'); ?></h2>
    <p class="fs-6">
	    <?php _e('You received a copy of the software completely free of charge and you can use it as is without any activation.', 'wc1c-main'); ?>
	    <?php _e('However, in order to receive timely, as well as necessary updates and improvements, it is necessary to activate the current environment.', 'wc1c-main'); ?>
    </p>
    <p class="fs-6">
	    <?php _e('Activation is vital for the performance of the plugin and its further active development. Dont ignore activation.', 'wc1c-main'); ?>
	    <?php _e('Each activation triggers a mechanism to improve the software you use.', 'wc1c-main'); ?>
    </p>
    <p class="fs-6">
	    <?php _e('In addition to supporting the software you use, additional features will be added.', 'wc1c-main'); ?>
    </p>
</div>

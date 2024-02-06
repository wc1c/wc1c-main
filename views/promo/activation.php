<?php defined('ABSPATH') || exit;

$activation_url = get_home_url('', add_query_arg(['section' => 'settings', 'do_settings' => 'activation']));

$text = sprintf
(
    '%s %s <hr>%s',
    __('Your copy of the free software has not been activated.', 'wc1c-main'),
    __('We recommend that you activate your copy of the free software for stable updates and better performance.', 'wc1c-main'),
    __('After activation, this section will disappear and will no longer be shown.', 'wc1c-main')
);
?>

<div class="row g-0">
    <div class="col-24 col-lg-17">
        <div class="mb-1 mt-2">
            <div class="me-lg-2 p-2 rounded-2 bg-white">

                <div class="fs-6 alert wc1c-yellow-alert p-2"><?php echo wp_kses_post($text); ?></div>

                <div class="">
                    <h2><?php _e('How to activate?', 'wc1c-main'); ?></h2>
                    <ul>
                        <li class="fs-6"><b>1.</b> <?php _e('Get an activation code in any available way. For example, on the official website.', 'wc1c-main'); ?> (<a target="_blank" href="//wc1c.info/market/code">wc1c.info/market/code</a>)</li>
                        <li class="fs-6"><b>2.</b> <?php _e('Enter the activation code in the plugin settings.', 'wc1c-main'); ?> (<a href="<?php printf('%s', $activation_url); ?>"><?php printf('%s', $activation_url); ?></a>)</li>
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

                    <h2><?php _e('ATTENTION! Activation does not add the possibility of extensions.', 'wc1c-main'); ?></h2>
                    <p class="fs-6">
                        <?php _e('Additional features are implemented in extensions! After activation, features from extensions are not activated.', 'wc1c-main'); ?>
                        <?php _e('To add features, you need to install extensions. They are supplied by separate plugins, after activation of which the necessary features are added.', 'wc1c-main'); ?>
                    </p>
                </div>

            </div>
        </div>
    </div>
    <div class="col-24 col-lg-7 p-0">

        <div class="alert alert-info border-0 mt-2 mw-100">
            <h4 class="alert-heading mt-0 mb-1 fs-6"><?php _e('Do not wait until something breaks!', 'wc1c-main'); ?></h4>
            <?php _e('Activate your current copy of the software.', 'wc1c-main'); ?>
            <hr>
            <?php _e('Buy code:', 'wc1c-main'); ?> <a target="_blank" href="//wc1c.info/market/code">wc1c.info/market/code</a>
        </div>

        <div class="alert alert-secondary border-0 mt-2 mw-100">
            <h4 class="alert-heading mt-0 mb-1 fs-6"><?php _e('No financial opportunity?', 'wc1c-main'); ?></h4>
            <?php _e('Take part in the development of the solution you use.', 'wc1c-main'); ?>
            <br/>
            <?php _e('Information on how to participate is available in the official documentation on the official website.', 'wc1c-main'); ?>
            <hr>
            <?php _e('Docs:', 'wc1c-main'); ?> <a target="_blank" href="//wc1c.info/docs">wc1c.info/docs</a>
        </div>

        <div class="alert alert-secondary border-0 mt-2 mw-100">
            <h4 class="alert-heading mt-0 mb-1 fs-6"><?php _e('Every activation counts!', 'wc1c-main'); ?></h4>
            <?php _e('By activating your project, you let the WC1C team know that the plugin is in active use.', 'wc1c-main'); ?>
            <br/>
            <?php _e('Also, you give a financial opportunity to release compatibility updates and add new features!', 'wc1c-main'); ?>
        </div>
    </div>
</div>
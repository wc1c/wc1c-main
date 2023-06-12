<?php defined('ABSPATH') || exit;?>

<form method="post" action="">
    <div class="row g-0">
        <div class="col-24 col-lg-17 p-0">
            <div class="pe-0 pe-lg-2">
                <?php wp_nonce_field('wc1c-admin-configurations-create-save', '_wc1c-admin-nonce'); ?>
                <div class="bg-white p-1 mb-2 rounded-3">
                    <table class="form-table wc1c-admin-form-table bg-white">
                        <?php
                        if(isset($args) && is_array($args))
                        {
                            $args['object']->generateHtml($args['object']->getFields(), true);
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-24 col-lg-7 p-0">
            <?php do_action('wc1c_admin_configurations_create_sidebar_before_show'); ?>

            <div class="card border-0 mt-0 p-0 mb-2 w-100" style="max-width: 100%;">
                <div class="card-body p-3">
                    <?php _e('Enter a name for the new configuration, select a scheme, and click the create configuration button.', 'wc1c-main'); ?>
                </div>
                <div class="card-footer p-3">
                    <p class="submit p-0 m-0">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Create configuration', 'wc1c-main'); ?>">
                    </p>
                </div>
            </div>

            <div class="alert alert-primary border-0">
                <h4 class="alert-heading mt-0 mb-1"><?php _e('More schemas for configurations', 'wc1c-main'); ?></h4>
		        <?php _e('Additional schemes are available in extensions on the official website.', 'wc1c-main'); ?>
                <br/>
                <?php _e('Each exchange scheme has a unique algorithm of operation and purpose. You need to choose only the scheme you need.', 'wc1c-main'); ?>
                <hr>
                <?php _e('Site:', 'wc1c-main'); ?> <a target="_blank" href="//wc1c.info/extensions">wc1c.info/extensions</a>
            </div>

            <?php do_action('wc1c_admin_configurations_create_sidebar_after_show'); ?>
        </div>
    </div>
</form>
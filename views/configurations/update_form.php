<?php defined('ABSPATH') || exit;?>

<div class="row g-0">
    <div class="col-24 col-lg-17">
        <div class="pe-0 pe-lg-2">
            <form method="post" action="<?php echo esc_url(add_query_arg('form', $args['object']->getId())); ?>">
                <?php wp_nonce_field('wc1c-admin-configurations-update-save', '_wc1c-admin-nonce'); ?>
                <div class="bg-white p-2 rounded-3 wc1c-toc-container">
                    <table class="form-table wc1c-admin-form-table">
                        <?php $args['object']->generateHtml($args['object']->getFields(), true); ?>
                    </table>
                </div>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save configuration', 'wc1c-main'); ?>">
                </p>
            </form>
        </div>
    </div>
    <div class="col-24 col-lg-7">
		<?php do_action('wc1c_admin_configurations_update_sidebar_show'); ?>
    </div>
</div>
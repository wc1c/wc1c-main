<?php defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Settings\ConnectionForm;

/** @var ConnectionForm $object */
$object = $args['object'];

?>

<form method="post" action="" class="mt-2">
    <div class="row g-0">
        <div class="col-24 col-lg-17 p-0">
            <div class="pe-0 pe-lg-2">
	            <?php wp_nonce_field('wc1c-admin-settings-save', '_wc1c-admin-nonce'); ?>
                <div class="wc1c-admin-settings wc1c-admin-connection bg-white rounded-3 mb-2 px-2">
                    <table class="form-table wc1c-admin-form-table wc1c-admin-settings-form-table">
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
			<?php do_action('wc1c_admin_settings_activation_sidebar_before_show'); ?>

            <div class="alert alert-warning border-0 mb-4 mw-100">
                <h4 class="alert-heading mt-0 mb-1 fs-6"><?php _e('Get code', 'wc1c-main'); ?></h4>
				<?php _e('The code can be obtained from the plugin website.', 'wc1c-main'); ?>
                <hr>
				<?php _e('Site:', 'wc1c-main'); ?> <a target="_blank" href="//wc1c.info/market/code">wc1c.info/market/code</a>
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

			<?php do_action('wc1c_admin_settings_activation_sidebar_after_show'); ?>
        </div>
    </div>
</form>
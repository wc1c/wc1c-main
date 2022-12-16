<?php defined('ABSPATH') || exit;?>

<form method="post" action="">
	<?php wp_nonce_field('wc1c-admin-settings-save', '_wc1c-admin-nonce'); ?>
    <div class="wc1c-admin-settings rounded-3 bg-white p-2 mt-2">
        <table class="form-table wc1c-admin-form-table wc1c-admin-settings-form-table">
		    <?php $args['object']->generate_html($args['object']->get_fields(), true); ?>
        </table>
    </div>
    <p class="submit">
	    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save settings', 'wc1c-main'); ?>">
    </p>
</form>
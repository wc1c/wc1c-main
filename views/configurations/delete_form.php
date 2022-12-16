<?php defined('ABSPATH') || exit;?>

<form method="post" action="">
	<?php wp_nonce_field('wc1c-admin-configurations-delete-save', '_wc1c-admin-nonce-configurations-delete'); ?>
    <div class="pt-2 rounded-3">
        <table class="form-table wc1c-admin-form-table">
            <?php
                if(isset($args) && is_array($args))
                {
                    $args['object']->generate_html($args['object']->get_fields(), true);
                }
            ?>
        </table>
    </div>
    <p class="submit p-1 pt-0 mt-1">
	    <input type="submit" name="submit" id="submit" class="button button-danger" value="<?php _e('Delete', 'wc1c-main'); ?>">
    </p>
</form>
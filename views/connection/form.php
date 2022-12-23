<?php defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Settings\ConnectionForm;

/** @var ConnectionForm $object */
$object = $args['object'];

?>

<form method="post" action="">
	<?php wp_nonce_field('wc1c-admin-settings-save', '_wc1c-admin-nonce'); ?>
    <?php if($object->status) : ?>
    <div class="wc1c-admin-settings wc1c-admin-connection bg-white rounded-3 mt-2 mb-2 px-2">
        <table class="form-table wc1c-admin-form-table wc1c-admin-settings-form-table">
		    <?php $object->generate_html($object->get_fields(), true); ?>
        </table>
    </div>
    <?php endif; ?>
    <div class="submit p-0 mt-3">
	    <?php
	        $button = __('Connect by WC1C site', 'wc1c-main');
            if($object->status)
            {
                $button = __('Disconnect', 'wc1c-main');
            }
        ?>

	    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e($button); ?>">
    </div>
</form>
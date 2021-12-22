<?php defined('ABSPATH') || exit; ?>

<div class="row">
    <div class="col pt-2 pb-2">
	    <?php
            $label = __('Back to all configurations', 'wc1c');
            wc1c()->templates()->adminBackLink($label, $args['back_url']);
	    ?>
    </div>
</div>

<div class="">
	<?php do_action(WC1C_ADMIN_PREFIX . 'configurations_form_create_show'); ?>
</div>
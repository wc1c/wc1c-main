<?php defined('ABSPATH') || exit; ?>

<div class="row">
    <div class="col">
        <div class="px-2">
            <?php
                $label = __('Back to all configurations', 'wc1c-main');
                wc1c()->views()->adminBackLink($label, $args['back_url']);
            ?>
        </div>
    </div>
</div>

<div class="">
	<?php do_action('wc1c_admin_configurations_form_create_show'); ?>
</div>
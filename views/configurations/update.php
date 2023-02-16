<?php defined('ABSPATH') || exit;?>

<div class="row g-0">
    <div class="col-24 p-0">
        <div class="px-2">
            <?php
                $label = __('Back to configurations list', 'wc1c-main');
                wc1c()->views()->adminBackLink($label, $args['back_url']);
            ?>
        </div>
        <div class="p-1 bg-white rounded-1 mb-3">
            <?php do_action('wc1c_admin_configurations_update_header_show'); ?>
        </div>
    </div>
</div>

<?php do_action('wc1c_admin_before_configurations_update_show'); ?>

<?php do_action('wc1c_admin_configurations_update_show'); ?>

<?php do_action('wc1c_admin_after_configurations_update_show'); ?>
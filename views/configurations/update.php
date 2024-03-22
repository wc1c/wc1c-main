<?php defined('ABSPATH') || exit;?>

<div class="row g-0">
    <div class="col-24 p-0">
        <div class="p-0 rounded-0 mb-2 mt-2 mx-0">
            <?php do_action('wc1c_admin_configurations_update_header_show'); ?>
        </div>
    </div>
</div>

<?php do_action('wc1c_admin_before_configurations_update_show'); ?>

<?php do_action('wc1c_admin_configurations_update_show'); ?>

<?php do_action('wc1c_admin_after_configurations_update_show'); ?>
<?php defined('ABSPATH') || exit; ?>

<?php do_action('wc1c_admin_before_tools_single_show'); ?>

<div class="row g-0">
    <div class="col p-0">
        <div class="px-2">
            <?php wc1c()->views()->adminBackLink($args['name'], $args['back_url']); ?>
        </div>
        <div class="bg-white p-2 rounded-3">
            <?php do_action('wc1c_admin_tools_single_show'); ?>
        </div>
    </div>
</div>

<?php do_action('wc1c_admin_after_tools_single_show'); ?>
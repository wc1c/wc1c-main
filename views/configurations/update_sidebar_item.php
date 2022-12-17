<?php defined('ABSPATH') || exit; ?>

<?php do_action('wc1c_admin_configurations_update_before_sidebar_item_show'); ?>

<div class="card border-0 mb-2 mt-0 p-0" style="max-width: 100%; <?php if(isset($args['css'])) printf('%s', wp_kses_post($args['css'])); ?>">
    <?php if(isset($args['header'])): ?>
    <div class="card-header p-2">
        <?php printf('%s', wp_kses_post($args['header'])); ?>
    </div>
    <?php endif; ?>
    <?php if(isset($args['body'])): ?>
    <div class="card-body p-0">
	    <?php printf('%s', wp_kses_post($args['body'])); ?>
    </div>
    <?php endif; ?>
    <?php if(isset($args['footer'])): ?>
    <div class="card-footer p-2">
	    <?php printf('%s', wp_kses_post($args['footer'])); ?>
    </div>
	<?php endif; ?>
</div>

<?php do_action('wc1c_admin_configurations_update_after_sidebar_item_show'); ?>
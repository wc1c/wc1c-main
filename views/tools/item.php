<?php defined('ABSPATH') || exit; ?>

<div class="mb-2 mt-2 w-100" style="border-radius: 0!important; border: 1px solid #f7f7f7;">
    <div class="card-body p-3">
        <h2 class="card-title mt-0">
            <?php printf('%s', sanitize_text_field($args['name'])); ?>
        </h2>
        <p class="card-text fs-6">
            <?php printf('%s', wp_kses_post($args['description'])); ?>
        </p>
    </div>
    <div class="card-footer bg-light p-3">
       <a class="text-decoration-none button button-primary" href="<?php echo esc_url($args['url']); ?>">
	       <?php _e('Open tool', 'wc1c-main'); ?>
       </a>
    </div>
</div>
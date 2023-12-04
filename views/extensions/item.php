<?php defined('ABSPATH') || exit; ?>

<div class="extensions-all-item mb-2 mt-2 rounded-1 border border-3 bg-white">
    <div class="card-header bg-light p-2 border-0">
        <h2 class="card-title mt-0 mb-0 float-start">
	        <?php printf('%s', wp_kses_post($args['object']->getMeta('name', __('none')))); ?>
        </h2>
        <div class="clearfix"></div>
    </div>
    <div class="card-body p-2">
        <div class="row g-0">
            <div class="col-24 col-md-15 col-lg-18 p-0">
                <p class="card-text mt-2 mb-2 fs-6">
		            <?php printf('%s', wp_kses_post($args['object']->getMeta('description', __('none')))); ?>
                </p>
            </div>
            <div class="col-24 mt-2 mt-md-0 col-md-9 col-lg-6 p-0">
                <ul class="list-group m-0">
                    <li class="list-group-item m-0 list-group-item-light">
                        <?php _e('ID:', 'wc1c-main'); ?>
                        <span class="badge bg-secondary"><?php printf('%s', wp_kses_post($args['id'])); ?></span>
                    </li>
                    <li class="list-group-item m-0">
                            <?php _e('Version:', 'wc1c-main'); ?>
                            <span class="badge btn-sm bg-success">
                            <?php printf('%s', wp_kses_post($args['object']->getMeta('version', __('none')))); ?>
                         </span>
                    </li>
                    <li class="list-group-item m-0">
		                <?php _e('Versions WC1C:', 'wc1c-main'); ?>
	                    <?php _e('from', 'wc1c-main'); ?>
                        <span class="badge btn-sm bg-success">
                            <?php printf('%s', wp_kses_post($args['object']->getMeta('version_wc1c_min', __('none')))); ?>
                         </span>
	                    <?php _e('to', 'wc1c-main'); ?>
                        <span class="badge btn-sm bg-success">
                            <?php printf('%s', wp_kses_post($args['object']->getMeta('version_wc1c_max', __('none')))); ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="card-footer p-0 border-0"></div>
</div>
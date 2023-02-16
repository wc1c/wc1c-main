<?php defined('ABSPATH') || exit;?>

<div class="row">
    <div class="col p-0">
        <div class="px-2">
			<?php
			$label = __('Back to all configurations', 'wc1c-main');
			wc1c()->views()->adminBackLink($label, $args['back_url']);
			?>
        </div>
    </div>
</div>

<div class="bg-white p-2 pt-3 pb-3 rounded-2">
	<?php
	    printf('%s <b>%s</b>', __('ID of the configuration to be deleted:', 'wc1c-main'), $args['configuration']->getId());
	?>
    <br/>
    <?php
        printf('%s <b>%s</b>', __('Name of the configuration to be deleted:', 'wc1c-main'), $args['configuration']->getName());
    ?>
    <br/>
    <?php
        printf('%s <b>%s</b>', __('Path of the configuration directory to be deleted:', 'wc1c-main'), $args['configuration']->getUploadDirectory());
    ?>
</div>

<div class="">
	<?php do_action('wc1c_admin_configurations_form_delete_show'); ?>
</div>

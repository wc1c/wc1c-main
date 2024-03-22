<?php defined('ABSPATH') || exit; ?>

<?php
	if(empty($_REQUEST['s']))
	{
?>
    <div class="alert alert-primary fs-6">
        <?php esc_html_e('To exchange data, at least one configuration must be created and configured.', 'wc1c-main'); ?>
        <?php esc_html_e('The number of settings and method of data exchange in the configuration depends on the scheme selected for exchange during creation.', 'wc1c-main'); ?>
    </div>
<?php
	}
?>

<h2 class="mt-0">
<?php
	if(!empty($_REQUEST['s']))
	{
		$search_text = wc_clean(wp_unslash($_REQUEST['s']));

        echo '<br/>';
        printf('%s %s', __( 'Configurations by query is not found, query:', 'wc1c-main' ), $search_text);
	}
    else
    {
	    esc_html_e('Configurations not found', 'wc1c-main');
    }
?>
</h2>

<?php
    if(empty($_REQUEST['s']))
    {
?>
    <p class="fs-6">
        <?php esc_html_e('For flexible exchange distribution, an unlimited number of configurations can be created.', 'wc1c-main'); ?>
        <?php esc_html_e('It is recommended to create at least two configurations: 1. To exchange nomenclature data, 2. To exchange orders data.', 'wc1c-main'); ?>
    </p>
    <a href="<?php echo esc_url_raw($args['url_create']); ?>" class="mt-2 mx-0 fs-6 btn-lg d-inline-block page-title-action">
        <?php _e('New configuration', 'wc1c-main'); ?>
    </a>
<?php
    }
?>
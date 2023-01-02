<?php defined('ABSPATH') || exit;?>

<h2><?php
	if(!empty($_REQUEST['s']))
	{
		$search_text = wc_clean(wp_unslash($_REQUEST['s']));
        printf('%s %s', __( 'Configurations by query is not found, query:', 'wc1c-main' ), $search_text);
	}
    else
    {
	    esc_html_e( 'Configurations not found.', 'wc1c-main' );
    }
?></h2>

<a href="<?php echo $args['url_create']; ?>" class="mt-2 btn-lg d-inline-block page-title-action">
    <?php _e('New configuration', 'wc1c-main'); ?>
</a>
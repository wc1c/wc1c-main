<?php defined('ABSPATH') || exit; ?>

<h1 class="wp-heading-inline"><?php _e('Integration with 1C', 'wc1c-main'); ?></h1>

<a href="<?php echo $args['url_create']; ?>" class="page-title-action">
	<?php _e('New configuration', 'wc1c-main'); ?>
</a>

<?php
    $settings = wc1c()->settings('connection');

    if($settings->get('login', false))
    {
        wc1c()->admin()->connectBox(__($settings->get('login', 'Undefined'), 'wc1c-main'), true);
    }
    else
    {
        wc1c()->admin()->connectBox(__( 'Connection to the WC1C', 'wc1c-main'));
    }
?>
<hr class="wp-header-end">

<?php
    if(wc1c()->context()->isAdmin())
    {
        wc1c()->admin()->notices()->output();
    }
?>
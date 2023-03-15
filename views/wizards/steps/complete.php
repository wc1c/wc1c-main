<?php defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Wizards\Setup\Complete;

if(!isset($args['step']))
{
    return;
}

/** @var Complete $wizard */
$step = $args['step'];

?>

<h1><?php _e('Installation completed!', 'wc1c-main'); ?></h1>

<p><?php _e('If something doesnt work, check the event logs!', 'wc1c-main'); ?></p>
<p><?php _e('Integration with 1C is complex and varied. The logs show all errors and non-standard exchange behavior!', 'wc1c-main'); ?></p>
<p><?php _e('Entries in journals in their native language!', 'wc1c-main'); ?></p>
<p><?php _e('Now you can proceed to using the WC1C plugin.', 'wc1c-main'); ?></p>

<p class="mt-4 actions step">
    <a href="<?php echo esc_url($args['back_url']); ?>" class="button button-primary button-large button-next">
        <?php _e('Go to use', 'wc1c-main'); ?>
    </a>
</p>
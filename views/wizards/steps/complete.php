<?php defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Wizards\Setup\Complete;

if(!isset($args['step']))
{
    return;
}

/** @var Complete $wizard */
$step = $args['step'];

?>

<h1><?php _e( 'Installation completed!', 'wc1c' ); ?></h1>
<p><?php _e( 'Now you can proceed to using the WC1C plugin.', 'wc1c' ); ?></p>

<p class="mt-4 actions step">
    <a href="<?php echo esc_url($args['back_url']); ?>" class="button button-primary button-large button-next">
        <?php _e('Go to use', 'wc1c-main'); ?>
    </a>
</p>
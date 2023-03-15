<?php defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Wizards\Setup\Database;

if(!isset($args['step']))
{
    return;
}

/** @var Database $wizard */
$step = $args['step'];

?>

<h1><?php _e( 'Creating tables in the database', 'wc1c-main' ); ?></h1>
<p><?php _e( 'If continue, the required tables will be created in the database.', 'wc1c-main' ); ?></p>

<form method="post" action="">
    <p class="mt-4 actions step">
        <?php wp_nonce_field('wc1c-admin-wizard-database', '_wc1c-admin-nonce'); ?>
        <input type="submit" name="submit" id="submit" class="button button-primary button-large button-next" value="<?php _e('Lets Go!', 'wc1c-main'); ?>">
    </p>
</form>
<?php defined('ABSPATH') || exit;?>

<form method="post" action="<?php echo esc_url(add_query_arg('form', $args['object']->getId())); ?>">
	<?php wp_nonce_field('wc1c-admin-'.$args['object']->getId().'-save', '_wc1c-admin-nonce-' . $args['object']->getId()); ?>

    <?php $args['object']->generateHtml($args['object']->getFields(), true); ?>
</form>
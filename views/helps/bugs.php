<?php defined('ABSPATH') || exit; ?>

<h2><?php _e( 'Found a bug?', 'wc1c' ); ?></h2>

<p>
    <?php _e('First of all, you need to make sure that a bug has been found and that it has not been fixed in updates before.', 'wc1c-main'); ?>
	<?php _e('If the bug is fixed in the updates, you just need to install the corrected version.', 'wc1c-main'); ?>
</p>
<p>
	<?php _e('Before reporting an error need to check:', 'wc1c-main'); ?>
</p>

<ul>
	<li><?php _e('Whether the settings for WordPress, WooCommerce, WC1C and their extensions are correct.', 'wc1c-main'); ?></li>
    <li><?php _e('Whether compatible versions of WordPress, WooCommerce, WC1C and their extensions are used. Compatibility can be found in the Environments section.', 'wc1c-main'); ?></li>
</ul>

<p>
	<?php _e('If all settings are made correctly and compatible products of the latest versions are used, but the error is still present, you must report it.', 'wc1c-main'); ?>
	<?php _e('Report a bug using the methods available to you. When reporting a bug, you must have a valid technical support code for the project on which the bug occurred.', 'wc1c-main'); ?>
</p>

<p>
	<a href=" <?php echo admin_url('admin.php?page=wc1с&section=tools&tool_id=environments'); ?>" class="button">
		<?php _e('Environments', 'wc1c-main'); ?>
	</a>
</p>
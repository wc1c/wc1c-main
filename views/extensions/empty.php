<?php defined('ABSPATH') || exit; ?>

<div class="extensions-alert mb-2 mt-2">
    <h3><?php _e('Extensions not found.', 'wc1c-main'); ?></h3>
    <p><?php _e('As soon as the extensions are installed, they will appear in this section.', 'wc1c-main'); ?></p>

	<?php
	printf
	(
		'<p>%s %s</p>',
		__('Information about all available official extensions is available on the website:', 'wc1c-main'),
		'<a href="https://wc1c.info/extensions" target=_blank>https://wc1c.info/extensions</a>'
	);
	?>
</div>
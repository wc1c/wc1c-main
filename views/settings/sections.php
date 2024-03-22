<?php defined('ABSPATH') || exit;

$admins = Wc1c\Main\Admin\Settings::instance();

$views = [];

foreach($admins->getSections() as $tab_key => $tab_name)
{
	$tab_key = esc_attr($tab_key);

	if(!isset($tab_name['visible']) && $tab_name['title'] !== true)
	{
		continue;
	}

	$class = $admins->getCurrentSection() === $tab_key ? ' class="current"' : '';
	$sold_url = esc_url(add_query_arg('do_settings', $tab_key));

	$views[$tab_key] = sprintf
	(
		'<a href="%s" %s>%s</a>',
		$sold_url,
		$class,
		esc_html($tab_name['title'])
	);
}

if(count($views) < 2)
{
	return;
}

echo "<ul class='subsubsub w-100 d-block float-none'>";
foreach($views as $class => $view)
{
	$views[$class] = "<li class='fs-6 $class'>$view";
}
echo wp_kses_post(implode(" |</li>", $views) . "</li>");
echo '</ul>';
<?php defined('ABSPATH') || exit;

$update = $args['object'];

$views = [];

foreach($update->getSections() as $tab_key => $tab_name)
{
	if(!isset($tab_name['visible']) && $tab_name['title'] !== true)
	{
		continue;
	}

	$class = $update->getCurrentSection() === $tab_key ? ' active' :'';
	$sold_url = esc_url(add_query_arg($update->getSectionKey(), $tab_key));

	$views[$tab_key] = sprintf
	(
		'<a href="%s" class="nav-link fs-6 border border-1 rounded-0 border-bottom-0 text-decoration-none %s">%s </a>',
		$sold_url,
		$class,
		$tab_name['title']
	);
}

if(count($views) < 1)
{
	return;
}

echo "<ul class='nav nav-tabs mt-0 mx-2'>";
foreach($views as $class => $view)
{
	$views[$class] = "<li class='nav-item pb-0 mb-0 me-1 $class'>$view";
}
echo wp_kses_post(implode("</li>", $views) . "</li>");
echo '</ul>';

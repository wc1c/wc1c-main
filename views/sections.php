<?php namespace Wc1c\Main;
defined('ABSPATH') || exit;

$admin = Admin::instance();

$nav = '<nav class="nav-tab-wrapper woo-nav-tab-wrapper pt-0">';

foreach($admin->getSections() as $tab_key => $tab_name)
{
	$tab_key = esc_html($tab_key);
	$tab_name['title'] = esc_html($tab_name['title']);

	if(!isset($tab_name['visible']) && $tab_name['title'] !== true)
	{
		continue;
	}

    if($tab_key === $admin->getCurrentSection())
    {
        $nav .= '<a href="' . admin_url('admin.php?page=wc1c&section=' . $tab_key) . '" class="nav-tab nav-tab-active">' . $tab_name['title'] . '</a>';
    }
    else
    {
        $nav .= '<a href="' . admin_url('admin.php?page=wc1c&section=' . $tab_key) . '" class="nav-tab">' . $tab_name['title'] . '</a>';
    }
}

printf('%s', wp_kses_post($nav));

echo '</nav>';
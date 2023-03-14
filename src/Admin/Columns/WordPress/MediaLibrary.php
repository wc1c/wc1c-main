<?php namespace Wc1c\Main\Admin\Columns\WordPress;

defined('ABSPATH') || exit;

use Wc1c\Main\Traits\SingletonTrait;

/**
 * MediaLibrary
 *
 * @package Wc1c\Main\Admin
 */
final class MediaLibrary
{
	use SingletonTrait;

	/**
	 * MediaLibrary constructor.
	 */
	public function __construct()
	{
		add_filter('manage_media_columns', [$this, 'manage_media_columns']);
		add_action('manage_media_custom_column', [$this, 'manage_media_custom_column'], 10, 2);

        add_filter('wc1c_admin_interface_media_library_lists_column', [$this, 'wc1c_admin_interface_media_library_lists_column'], 10, 2);
    }

	/**
	 * Adding a column to the list of media files for displaying 1C information
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function manage_media_columns($columns): array
    {
		$columns_after =
		[
			'wc1c' => __('1C information', 'wc1c-main'),
		];

		return array_merge($columns, $columns_after);
	}

	/**
	 * Information from 1C
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function manage_media_custom_column($column, $post_id)
	{
		if('wc1c' === $column)
		{
            $content = '';

            if(has_filter('wc1c_admin_interface_media_library_lists_column'))
            {
                $content = apply_filters('wc1c_admin_interface_media_library_lists_column', $content, $post_id);
            }

            if('' === $content)
            {
                $content .= '<span class="na">' . __('Not found', 'wc1c-main') . '</span>';
            }

			printf('%s', wp_kses_post($content));
		}
	}

    /**
     *
     * @param $content
     * @param $post_id
     *
     * @return string
     */
    public function wc1c_admin_interface_media_library_lists_column($content, $post_id): string
    {
        $schema_id = get_post_meta($post_id, '_wc1c_schema_id', true);
        $config_id = get_post_meta($post_id, '_wc1c_configuration_id', true);
        $time = get_post_meta($post_id, '_wc1c_time', true);

        if($time)
        {
            $content .= '<span class="na">' . __('Activity:', 'wc1c-main') . ' ';
            $content .= sprintf(_x('%s ago.', '%s = human-readable time difference', 'wc1c-main'), human_time_diff($time, current_time('timestamp', true)));
            $content .= '</span><br/>';
        }

        if($schema_id)
        {
            $content .= '<span class="na">' . __('Schema ID:', 'wc1c-main') . ' ' . $schema_id . '</span>';
        }

        if($config_id)
        {
            $content .= '<br/><span class="na">' . __('Configuration ID:', 'wc1c-main') . ' ' . $config_id . '</span>';
        }

        return $content;
    }
}
<?php namespace Digiom\Woplucore\Abstracts;

defined('ABSPATH') || exit;

/**
 * TableAbstract
 *
 * @package Digiom\Woplucore\Abstracts
 */
abstract class TableAbstract
{
	/**
	 * @var array The current list of items
	 */
	public $items;

	/**
	 * @var array Various information about the current table
	 */
	protected $_args;

	/**
	 * @var array Various information needed for displaying the pagination
	 */
	protected $_pagination_args = [];

	/**
	 * @var object The current screen
	 */
	protected $screen;

	/**
	 * @var array Cached bulk actions
	 */
	private $_actions;

	/**
	 * @var string Cached pagination output
	 */
	private $_pagination;

	/**
	 * @var array The view switcher modes
	 */
	protected $modes = [];

	/**
	 * @var array Stores the value returned by ->get_column_info()
	 */
	protected $_column_headers;

	/**
	 * Constructor
	 *
	 * The child class should call this constructor from its own constructor to override
	 * the default $args
	 *
	 * @param array|string $args {
	 *     Array or string of arguments.
	 *
	 *     @type string $plural   Plural value used for labels and the objects being listed.
	 *                            This affects things such as CSS class-names and nonces used
	 *                            in the list table, e.g. 'posts'. Default empty.
	 *     @type string $singular Singular label for an object being listed, e.g. 'post'.
	 *                            Default empty
	 *     @type bool   $ajax     Whether the list table supports Ajax. This includes loading
	 *                            and sorting data, for example. If true, the class will call
	 *                            the _js_vars() method in the footer to provide variables
	 *                            to any scripts handling Ajax events. Default false.
	 *     @type string $screen   String containing the hook name used to determine the current
	 *                            screen. If left null, the current screen will be automatically set.
	 *                            Default null.
	 * }
	 */
	public function __construct($args = [])
	{
		$args = wp_parse_args
		(
			$args,
            [
				'plural' => '',
				'singular' => '',
				'ajax' => false,
				'screen' => null,
			]
		);

		$this->screen = convert_to_screen($args['screen']);

		add_filter("manage_{$this->screen->id}_columns", [$this, 'getColumns'], 0);

		if(!$args['plural'])
		{
			$args['plural'] = $this->screen->base;
		}

		$args['plural'] = sanitize_key($args['plural']);
		$args['singular'] = sanitize_key($args['singular']);

		$this->_args = $args;

		if($args['ajax'])
		{
			add_action('admin_footer', [$this, 'jsVars']);
		}

		if(empty($this->modes))
		{
			$this->modes =
            [
                'list' => __('List View'),
                'excerpt' => __('Excerpt View'),
            ];
		}
	}

	/**
	 * Checks the current user's permissions
	 */
	public function ajaxUserCan()
	{
		die('function Table::ajaxUserCan() must be over-ridden in a sub-class.');
	}

	/**
	 * Prepares the list of items for displaying
	 */
	abstract public function prepareItems();

	/**
	 * An internal method that sets all the necessary pagination arguments
	 *
	 * @param array|string $args Array or string of arguments with information about the pagination.
	 */
	protected function setPaginationArgs($args)
	{
		$args = wp_parse_args
		(
			$args,
			[
				'total_items' => 0,
				'total_pages' => 0,
				'per_page' => 0,
			]
		);

		if(!$args['total_pages'] && $args['per_page'] > 0)
		{
			$args['total_pages'] = ceil($args['total_items'] / $args['per_page']);
		}

		// Redirect if page number is invalid and headers are not already sent
		if(!headers_sent() && !wp_doing_ajax() && $args['total_pages'] > 0 && $this->getPagenum() > $args['total_pages'])
		{
			wp_redirect(add_query_arg('paged', $args['total_pages']));
			exit;
		}

		$this->_pagination_args = $args;
	}

	/**
	 * Access the pagination args
	 *
	 * @param string $key Pagination argument to retrieve. Common values include 'total_items',
	 *                    'total_pages', 'per_page', or 'infinite_scroll'.
	 *
	 * @return int|void Number of items that correspond to the given pagination argument.
	 */
	public function getPaginationArg(string $key)
	{
		if('page' === $key)
		{
			return $this->getPagenum();
		}

		if(isset($this->_pagination_args[$key]))
		{
			return $this->_pagination_args[$key];
		}
	}

	/**
	 * Whether the table has items to display or not
	 *
	 * @return bool
	 */
	public function hasItems(): bool
	{
		return !empty($this->items);
	}

	/**
	 * Message to be displayed when there are no items
	 */
	public function noItems()
	{
		_e('No items found.');
	}

	/**
	 * Displays the search box
	 *
	 * @param string $text The 'submit' button label
	 * @param string $input_id ID attribute value for the search input field
	 */
	public function searchBox(string $text, string $input_id)
	{
		if(empty($_REQUEST['s']) && ! $this->hasItems())
		{
			return;
		}

		$input_id .= '-search-input';

		if(!empty($_REQUEST['orderby']))
		{
			echo '<input type="hidden" name="orderby" value="' . esc_attr($_REQUEST['orderby']) . '" />';
		}
		if(!empty($_REQUEST['order']))
		{
			echo '<input type="hidden" name="order" value="' . esc_attr($_REQUEST['order']) . '" />';
		}
		if(!empty($_REQUEST['post_mime_type']))
		{
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr($_REQUEST['post_mime_type']) . '" />';
		}
		if(!empty($_REQUEST['detached']))
		{
			echo '<input type="hidden" name="detached" value="' . esc_attr($_REQUEST['detached']) . '" />';
		}
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php _admin_search_query(); ?>"/>
			<?php submit_button($text, '', '', false, array('id' => 'search-submit')); ?>
		</p>
		<?php
	}

	/**
	 * Get an associative array ( id => link ) with the list
	 * of views available on this table
	 *
	 * @return array
	 */
	protected function getViews(): array
	{
		return [];
	}

	/**
	 * Display the list of views available on this table
	 */
	public function views()
	{
		$views = $this->getViews();

		/**
		 * Filters the list of available list table views.
		 *
		 * The dynamic portion of the hook name, `$this->screen->id`, refers
		 * to the ID of the current screen, usually a string.
		 *
		 * @param string[] $views An array of available list table views.
		 */
		$views = apply_filters("views_{$this->screen->id}", $views);

		if(empty($views))
		{
			return;
		}

		$this->screen->render_screen_reader_content('heading_views');

		echo "<ul class='subsubsub'>\n";
		foreach($views as $class => $view)
		{
			$views[$class] = "\t<li class='$class'>$view";
		}
		echo implode(" |</li>\n", $views) . "</li>\n";
		echo '</ul>';
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table
	 *
	 * @return array
	 */
	protected function getBulkActions(): array
	{
		return [];
	}

	/**
	 * Display the bulk actions dropdown
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 * This is designated as optional for backward compatibility.
	 */
	protected function bulkActions(string $which = '')
	{
		if(is_null($this->_actions))
		{
			$this->_actions = $this->getBulkActions();

			/**
			 * Filters the list table Bulk Actions drop-down.
			 *
			 * The dynamic portion of the hook name, `$this->screen->id`, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * This filter can currently only be used to remove bulk actions.
			 *
			 * @param string[] $actions An array of the available bulk actions
			 */
			$this->_actions = apply_filters("bulk_actions-{$this->screen->id}", $this->_actions);
			$two = '';
		}
		else
		{
			$two = '2';
		}

		if(empty($this->_actions))
		{
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr($which) . '" class="screen-reader-text">' . __('Select bulk action') . '</label>';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr($which) . "\">\n";
		echo '<option value="-1">' . __('Bulk Actions') . "</option>\n";

		foreach($this->_actions as $name => $title)
		{
			$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

			echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
		}

		echo "</select>\n";

		submit_button(__('Apply'), 'action', '', false, array('id' => "doaction$two"));
		echo "\n";
	}

	/**
	 * Get the current action selected from the bulk actions dropdown
	 *
	 * @return string|false The action name or False if no action was selected
	 */
	public function currentAction()
	{
		if(isset($_REQUEST['filter_action']) && ! empty($_REQUEST['filter_action']))
		{
			return false;
		}

		if(isset($_REQUEST['action']) && -1 !== $_REQUEST['action'])
		{
			return $_REQUEST['action'];
		}

		if(isset($_REQUEST['action2']) && -1 !== $_REQUEST['action2'])
		{
			return $_REQUEST['action2'];
		}

		return false;
	}

	/**
	 * Generate row actions div
	 *
	 * @param string[] $actions An array of action links
	 * @param bool $always_visible Whether the actions should be always visible
	 *
	 * @return string
	 */
	protected function rowActions($actions, $always_visible = false)
	{
		$action_count = count($actions);
		$i = 0;

		if(!$action_count)
		{
			return '';
		}

		$out = '<div class="' . ($always_visible ? 'row-actions visible' : 'row-actions') . '">';
		foreach($actions as $action => $link)
		{
			++$i;

			($i === $action_count) ? $sep = '' : $sep = ' | ';

			$out .= "<span class='$action'>$link$sep</span>";
		}
		$out .= '</div>';

		$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __('Show more details') . '</span></button>';

		return $out;
	}

	/**
	 * Display a view switcher
	 *
	 * @param string $current_mode
	 */
	protected function viewSwitcher($current_mode)
	{
		?>
		<input type="hidden" name="mode" value="<?php echo esc_attr($current_mode); ?>"/>
		<div class="view-switch">
			<?php
			foreach($this->modes as $mode => $title)
			{
				$classes = array('view-' . $mode);
				if($current_mode === $mode)
				{
					$classes[] = 'current';
				}
				printf(
					"<a href='%s' class='%s' id='view-switch-$mode'><span class='screen-reader-text'>%s</span></a>\n",
					esc_url(add_query_arg('mode', $mode)),
					implode(' ', $classes),
					$title
				);
			}
			?>
		</div>
		<?php
	}

	/**
	 * Get the current page number
	 *
	 * @return int
	 */
	public function getPagenum()
	{
		$page_num = isset($_REQUEST['paged']) ? absint($_REQUEST['paged']) : 0;

		if(isset($this->_pagination_args['total_pages']) && $page_num > $this->_pagination_args['total_pages'])
		{
			$page_num = $this->_pagination_args['total_pages'];
		}

		return max(1, $page_num);
	}

	/**
	 * Get number of items to display on a single page
	 *
	 * @param string $option
	 * @param int $default
	 *
	 * @return int
	 */
	protected function getItemsPerPage($option, $default = 20)
	{
		$per_page = (int) get_user_option($option);
		if(empty($per_page) || $per_page < 1)
		{
			$per_page = $default;
		}

		/**
		 * Filters the number of items to be displayed on each page of the list table.
		 *
		 * The dynamic hook name, $option, refers to the `per_page` option depending
		 * on the type of list table in use. Possible values include: 'edit_comments_per_page',
		 * 'sites_network_per_page', 'site_themes_network_per_page', 'themes_network_per_page',
		 * 'users_network_per_page', 'edit_post_per_page', 'edit_page_per_page',
		 * 'edit_{$post_type}_per_page', etc.
		 *
		 * @param int $per_page Number of items to be displayed. Default 20
		 */
		return (int) apply_filters($option, $per_page);
	}

	/**
	 * Display the pagination
	 *
	 * @param string $which
	 */
	protected function pagination($which)
	{
		if(empty($this->_pagination_args))
		{
			return;
		}

		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];
		$infinite_scroll = false;

		if(isset($this->_pagination_args['infinite_scroll']))
		{
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}

		if('top' === $which && $total_pages > 1)
		{
			$this->screen->render_screen_reader_content('heading_pagination');
		}

		$output = '<span class="displaying-num">' . sprintf
			(
			/* translators: %s: Number of items. */
				_n('%s item', '%s items', $total_items),
				number_format_i18n($total_items)
			) . '</span>';

		$current = $this->getPagenum();
		$removable_query_args = wp_removable_query_args();

		$current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

		$current_url = remove_query_arg($removable_query_args, $current_url);

		$page_links = [];

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = false;
		$disable_last  = false;
		$disable_prev  = false;
		$disable_next  = false;

		if($current === 1)
		{
			$disable_first = true;
			$disable_prev  = true;
		}
		if($current === 2)
		{
			$disable_first = true;
		}
		if($current === $total_pages)
		{
			$disable_last = true;
			$disable_next = true;
		}
		if($current === $total_pages - 1)
		{
			$disable_last = true;
		}

		if($disable_first)
		{
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		}
		else
		{
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url(remove_query_arg('paged', $current_url)),
				__('First page'),
				'&laquo;'
			);
		}

		if($disable_prev)
		{
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		}
		else
		{
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url(add_query_arg('paged', max(1, $current - 1), $current_url)),
				__('Previous page'),
				'&lsaquo;'
			);
		}

		if('bottom' === $which)
		{
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __('Current Page') . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		}
		else
		{
			$html_current_page = sprintf(
				"%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __('Current Page') . '</label>',
				$current,
				strlen($total_pages)
			);
		}

		$html_total_pages = sprintf("<span class='total-pages'>%s</span>", number_format_i18n($total_pages));
		$page_links[]     = $total_pages_before . sprintf(
			/* translators: 1: Current page, 2: Total pages. */
				_x('%1$s of %2$s', 'paging'),
				$html_current_page,
				$html_total_pages
			) . $total_pages_after;

		if($disable_next)
		{
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		}
		else
		{
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url(add_query_arg('paged', min($total_pages, $current + 1), $current_url)),
				__('Next page'),
				'&rsaquo;'
			);
		}

		if($disable_last)
		{
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		}
		else
		{
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url(add_query_arg('paged', $total_pages, $current_url)),
				__('Last page'),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';

		if(!empty($infinite_scroll))
		{
			$pagination_links_class .= ' hide-if-js';
		}

		$output .= "\n<span class='$pagination_links_class'>" . implode("\n", $page_links) . '</span>';

		if($total_pages)
		{
			$page_class = $total_pages < 2 ? ' one-page' : '';
		}
		else
		{
			$page_class = ' no-pages';
		}

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @return array
	 */
	abstract public function getColumns();

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 *
	 * @return array
	 */
	protected function getSortableColumns()
	{
		return [];
	}

	/**
	 * Gets the name of the default primary column
	 *
	 * @return string Name of the default primary column, in this case, an empty string
	 */
	protected function getDefaultPrimaryColumnName()
	{
		$columns = $this->getColumns();
		$column  = '';

		if(empty($columns))
		{
			return $column;
		}

		// We need a primary defined so responsive views show something,
		// so let's fall back to the first non-checkbox column.
		foreach($columns as $col => $column_name)
		{
			if('cb' === $col)
			{
				continue;
			}

			$column = $col;
			break;
		}

		return $column;
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @return string The name of the primary column.
	 */
	protected function getPrimaryColumnName(): string
	{
		$columns = get_column_headers($this->screen);

		// If the primary column doesn't exist fall back to the
		// first non-checkbox column
		$default = $this->getDefaultPrimaryColumnName();

		/**
		 * Filters the name of the primary column for the current list table.
		 *
		 * @param string $default Column name default for the specific list table, e.g. 'name'.
		 * @param string $context Screen ID for specific list table, e.g. 'plugins'.
		 */
		$column = apply_filters('list_table_primary_column', $default, $this->screen->id);

		if(empty($column) || ! isset($columns[$column]))
		{
			$column = $default;
		}

		return $column;
	}

	/**
	 * Get a list of all, hidden and sortable columns, with filter applied
	 *
	 * @return array
	 */
	protected function getColumnInfo(): array
	{
		if(isset($this->_column_headers) && is_array($this->_column_headers))
		{
			// Back-compat for list tables that have been manually setting $_column_headers for horse reasons.
			// In 4.3, we added a fourth argument for primary column.
			$column_headers = array([], [], [], $this->getPrimaryColumnName());
			foreach($this->_column_headers as $key => $value)
			{
				$column_headers[$key] = $value;
			}

			return $column_headers;
		}

		$columns = get_column_headers($this->screen);
		$hidden  = get_hidden_columns($this->screen);

		$sortable_columns = $this->getSortableColumns();

		/**
		 * Filters the list table sortable columns for a specific screen.
		 *
		 * The dynamic portion of the hook name, `$this->screen->id`, refers
		 * to the ID of the current screen, usually a string.
		 *
		 * @param array $sortable_columns An array of sortable columns
		 */
		$_sortable = apply_filters("manage_{$this->screen->id}_sortable_columns", $sortable_columns);

		$sortable = [];
		foreach($_sortable as $id => $data)
		{
			if(empty($data))
			{
				continue;
			}

			$data = (array) $data;
			if(!isset($data[1]))
			{
				$data[1] = false;
			}

			$sortable[$id] = $data;
		}

		$primary = $this->getPrimaryColumnName();
		$this->_column_headers = array($columns, $hidden, $sortable, $primary);

		return $this->_column_headers;
	}

	/**
	 * Return number of visible columns
	 *
	 * @return int
	 */
	public function getColumnCount()
	{
		list ($columns, $hidden) = $this->getColumnInfo();
		$hidden = array_intersect(array_keys($columns), array_filter($hidden));

		return count($columns) - count($hidden);
	}

	/**
	 * Print column headers, accounting for hidden and sortable columns
	 *
	 * @staticvar int $cb_counter
	 *
	 * @param bool $with_id Whether to set the id attribute or not
	 */
	public function printColumnHeaders($with_id = true)
	{
		list($columns, $hidden, $sortable, $primary) = $this->getColumnInfo();

		$current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		$current_url = remove_query_arg('paged', $current_url);

		if(isset($_GET['orderby']))
		{
			$current_orderby = $_GET['orderby'];
		}
		else
		{
			$current_orderby = '';
		}

		if(isset($_GET['order']) && 'desc' === $_GET['order'])
		{
			$current_order = 'desc';
		}
		else
		{
			$current_order = 'asc';
		}

		if(!empty($columns['cb']))
		{
			static $cb_counter = 1;
			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __('Select All') . '</label>'
			                 . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}

		foreach($columns as $column_key => $column_display_name)
		{
			$class = array('manage-column', "column-$column_key");

			if(in_array($column_key, $hidden, true))
			{
				$class[] = 'hidden';
			}

			if('cb' === $column_key)
			{
				$class[] = 'check-column';
			}
			elseif(in_array($column_key, array('posts', 'comments', 'links')))
			{
				$class[] = 'num';
			}

			if($column_key === $primary)
			{
				$class[] = 'column-primary';
			}

			if(isset($sortable[$column_key]))
			{
				list($orderby, $desc_first) = $sortable[$column_key];

				if($current_orderby === $orderby)
				{
					$order   = 'asc' === $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				}
				else
				{
					$order   = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$column_display_name = '<a href="' . esc_url(add_query_arg(compact('orderby', 'order'), $current_url)) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$tag = ('cb' === $column_key) ? 'td' : 'th';
			$scope = ('th' === $tag) ? 'scope="col"' : '';
			$id = $with_id ? "id='$column_key'" : '';

			if( ! empty($class))
			{
				$class = "class='" . implode(' ', $class) . "'";
			}

			echo "<$tag $scope $id $class>$column_display_name</$tag>";
		}
	}

	/**
	 * Displays the table
	 */
	public function display()
	{
		$singular = $this->_args['singular'];

		$this->displayTablenav('top');

		$this->screen->render_screen_reader_content('heading_list');
		?>
		<table class="wp-list-table <?php echo implode(' ', $this->getTableClasses()); ?>">
			<thead>
			<tr>
				<?php $this->printColumnHeaders(); ?>
			</tr>
			</thead>

			<tbody id="the-list"
				<?php
				if($singular)
				{
					echo " data-wp-lists='list:$singular'";
				}
				?>
			>
			<?php $this->displayRowsOrPlaceholder(); ?>
			</tbody>

			<tfoot>
			<tr>
				<?php $this->printColumnHeaders(false); ?>
			</tr>
			</tfoot>

		</table>
		<?php
		$this->displayTablenav('bottom');
	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag
	 *
	 * @return array List of CSS classes for the table tag
	 */
	protected function getTableClasses()
	{
		return array('widefat', 'fixed', 'striped', $this->_args['plural']);
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @param string $which
	 */
	protected function displayTablenav($which)
	{
		if('top' === $which)
		{
			wp_nonce_field('bulk-' . $this->_args['plural']);
		}
		?>
		<div class="tablenav <?php echo esc_attr($which); ?>">

			<?php if($this->hasItems()) : ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulkActions($which); ?>
				</div>
			<?php
			endif;
			$this->extraTablenav($which);
			$this->pagination($which);
			?>

			<br class="clear"/>
		</div>
		<?php
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @param string $which
	 */
	protected function extraTablenav($which) {}

	/**
	 * Generate the tbody element for the list table
	 */
	public function displayRowsOrPlaceholder()
	{
		if($this->hasItems())
		{
			$this->displayRows();
		}
		else
		{
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->getColumnCount() . '">';
			$this->noItems();
			echo '</td></tr>';
		}
	}

	/**
	 * Generate the table rows
	 */
	public function displayRows()
	{
		foreach($this->items as $item)
		{
			$this->singleRow($item);
		}
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @param object $item The current item
	 */
	public function singleRow($item)
	{
		echo '<tr>';
		$this->singleRowColumns($item);
		echo '</tr>';
	}

	/**
	 * @param object $item
	 * @param string $column_name
	 */
	protected function columnDefault($item, $column_name) {}

	/**
	 * @param object $item
	 */
	protected function columnCb($item) {}

	/**
	 * Generates the columns for a single row of the table
	 *
	 * @param object $item The current item
	 */
	protected function singleRowColumns($item)
	{
		list($columns, $hidden, $sortable, $primary) = $this->getColumnInfo();

		foreach($columns as $column_name => $column_display_name)
		{
			$classes = "$column_name column-$column_name";

			if($primary === $column_name)
			{
				$classes .= ' has-row-actions column-primary';
			}

			if(in_array($column_name, $hidden, true))
			{
				$classes .= ' hidden';
			}

			// Comments column uses HTML in the display name with screen reader text.
			// Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
			$data = 'data-colname="' . wp_strip_all_tags($column_display_name) . '"';

			$attributes = "class='$classes' $data";

			if('cb' === $column_name)
			{
				echo '<th scope="row" class="check-column">';
				echo $this->columnCb($item);
				echo '</th>';
			}
			elseif(method_exists($this, '_column_' . $column_name))
			{
				echo $this->{'_column_' . $column_name}($item, $classes, $data, $primary);
			}
			elseif(method_exists($this, 'column_' . $column_name))
			{
				echo "<td $attributes>";
				echo $this->{'column_' . $column_name}($item);
				echo $this->handleRowActions($item, $column_name, $primary);
				echo '</td>';
			}
			else
			{
				echo "<td $attributes>";
				echo $this->columnDefault($item, $column_name);
				echo $this->handleRowActions($item, $column_name, $primary);
				echo '</td>';
			}
		}
	}

	/**
	 * Generates and display row actions links for the list table
	 *
	 * @param object $item The item being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary Primary column name.
	 *
	 * @return string The row actions HTML, or an empty string if the current column is the primary column.
	 */
	protected function handleRowActions($item, string $column_name, string $primary): string
	{
		return $column_name === $primary ? '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __('Show more details') . '</span></button>' : '';
	}

	/**
	 * Handle an incoming ajax request (called from admin-ajax.php)
	 */
	public function ajaxResponse()
	{
		$this->prepareItems();

		ob_start();

		if(!empty($_REQUEST['no_placeholder']))
		{
			$this->displayRows();
		}
		else
		{
			$this->displayRowsOrPlaceholder();
		}

		$rows = ob_get_clean();

		$response = ['rows' => $rows];

		if(isset($this->_pagination_args['total_items']))
		{
			$response['total_items_i18n'] = sprintf(
			/* translators: Number of items. */
				_n('%s item', '%s items', $this->_pagination_args['total_items']),
				number_format_i18n($this->_pagination_args['total_items'])
			);
		}
		if(isset($this->_pagination_args['total_pages']))
		{
			$response['total_pages'] = $this->_pagination_args['total_pages'];
			$response['total_pages_i18n'] = number_format_i18n($this->_pagination_args['total_pages']);
		}

		die(wp_json_encode($response));
	}

	/**
	 * Send required variables to JavaScript land
	 */
	public function jsVars()
	{
		$args = [
			'class' => get_class($this),
			'screen' =>
            [
                'id' => $this->screen->id,
                'base' => $this->screen->base,
            ],
		];

		printf("<script type='text/javascript'>list_args = %s;</script>\n", wp_json_encode($args));
	}
}
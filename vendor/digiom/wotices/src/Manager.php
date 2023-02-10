<?php namespace Digiom\Wotices;

defined('ABSPATH') || exit;

use ArrayIterator;
use IteratorAggregate;
use Countable;
use Exception;
use InvalidArgumentException;
use Traversable;
use WP_Screen;
use WP_User;
use Digiom\Wotices\Abstracts\NoticeAbstract;
use Digiom\Wotices\Interfaces\ManagerInterface;
use Digiom\Wotices\Types\ErrorNotice;
use Digiom\Wotices\Types\InfoNotice;
use Digiom\Wotices\Types\SuccessNotice;
use Digiom\Wotices\Types\UpdateNotice;
use Digiom\Wotices\Types\WarningNotice;

/**
 * Class Manager
 *
 * @package Digiom\Wotices
 */
class Manager implements ManagerInterface, Countable, IteratorAggregate
{
	/**
	 * @var array
	 */
	protected $args = [];

	/**
	 * @var string
	 */
	protected $transient_name;

	/**
	 * @var int
	 */
	protected $transient_expiration = 900;

	/**
	 * @var bool
	 */
	protected $auto_save = false;

	/**
	 * @var bool
	 */
	protected $use_admin_notices = false;

	/**
	 * @var bool
	 */
	protected $use_all_admin_notices = false;

	/**
	 * @var bool
	 */
	protected $use_network_admin_notices = false;

	/**
	 * @var bool
	 */
	protected $use_user_admin_notices = false;

	/**
	 * @var array
	 */
	protected $notices_types =
	[
		'info' => InfoNotice::class,
		'error' => ErrorNotice::class,
		'warning' => WarningNotice::class,
		'update' => UpdateNotice::class,
		'success' => SuccessNotice::class
	];

	/**
	 * @var NoticeAbstract[] array
	 */
	protected $notices = [];

	/**
	 * Allowed HTML in the data.
	 *
	 * @var array
	 */
	protected $allowed_html =
	[
		'div' =>
        [
			'class' => [],
			'id' => []
		],
		'span' =>
        [
            'class' => [],
            'id' => []
        ],
		'p' => [],
		'a' =>
		[
			'href' => [],
			'rel'  => [],
		],
		'em' => [],
		'strong' => [],
		'br' => [],
	];

	/**
	 * Manager constructor.
	 *
	 * @param $name
	 * @param $args
	 */
	public function __construct($name, $args)
	{
		$defaultArgs =
		[
			'expiration' => 900,
			'auto_save' => false,
			'admin_notices' => false,
			'all_admin_notices' => false,
			'network_admin_notices' => false,
			'user_admin_notices' => false,
		];

		$this->args = wp_parse_args($args, $defaultArgs);

		$this->setTransientName($name);
		$this->setTransientExpiration($this->args['expiration']);

		if($this->args['auto_save'])
		{
			$this->setAutoSave(true);
		}

		if($this->args['admin_notices'])
		{
			$this->setUseAdminNotices(true);
			add_action('admin_notices', [$this, 'output']);
		}

		if($this->args['all_admin_notices'])
		{
			$this->setUseAllAdminNotices(true);
			add_action('all_admin_notices', [$this, 'output']);
		}

		if($this->args['network_admin_notices'])
		{
			$this->setUseNetworkAdminNotices(true);
			add_action('network_admin_notices', [$this, 'output']);
		}

		if($this->args['user_admin_notices'])
		{
			$this->setUseUserAdminNotices(true);
			add_action('user_admin_notices', [$this, 'output']);
		}

		$data = $this->fetch();

		if(is_array($data))
		{
			$this->notices = $data;
		}

        add_action('wp_ajax_' . $this->getTransientName(), [$this, 'ajaxHandler']);
	}

	/**
	 * @return string
	 */
	public function getTransientName()
	{
		return $this->transient_name;
	}

	/**
	 * @param string $transient_name
	 */
	public function setTransientName($transient_name)
	{
		$this->transient_name = $transient_name;
	}

	/**
	 * @return int
	 */
	public function getTransientExpiration()
	{
		return $this->transient_expiration;
	}

	/**
	 * @param int $transient_expiration
	 */
	public function setTransientExpiration($transient_expiration)
	{
		$this->transient_expiration = $transient_expiration;
	}

	/**
	 * @return bool
	 */
	public function isAutoSave()
	{
		return $this->auto_save;
	}

	/**
	 * @param bool $auto_save
	 */
	public function setAutoSave($auto_save)
	{
		$this->auto_save = $auto_save;
	}

	/**
	 * @return array
	 */
	public function getAllowedHtml()
	{
		return $this->allowed_html;
	}

	/**
	 * @param array $allowed_html
	 */
	public function setAllowedHtml($allowed_html)
	{
		$this->allowed_html = $allowed_html;
	}

	/**
	 * Get notice by ID
	 *
	 * @param $notice_id
	 *
	 * @return array|null
	 */
	public function get($notice_id)
	{
		if(empty($notice_id))
		{
			return null;
		}

		return $this->has($notice_id) ? $this->notices[$notice_id] : null;
	}

	/**
	 * @param $id
	 * @param array $args
	 *
	 * @return boolean
	 */
	public function add($id, $args = [])
	{
		if(!$this->has($id))
		{
			$this->notices[$id] = $args;
			return true;
		}

		return false;
	}

	/**
	 * Notice builder
	 *
	 * @param array $args
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function create($args = [])
	{
		$defaultArgs =
		[
			'prefix' => '',
			'id' => random_int(3, 500),
			'type' => 'info',
			'data' => '',
			'extra_data' => '',
			'dismissible' => false,
			'context'=>
			[
				'user_ids' => get_current_user_id(),
				'screen_ids'=> [],
				'capabilities' => [],
				'post_ids' => [],
			]
		];

		$args = wp_parse_args($args, $defaultArgs);

		$notice_type_class = 'InfoNotice';

		if(isset($this->notices_types[$args['type']]))
		{
			$notice_type_class = $this->notices_types[$args['type']];
		}

		$args['data'] = wp_kses($args['data'], $this->getAllowedHtml());
		$args['extra_data'] = wp_kses($args['extra_data'], $this->getAllowedHtml());

		if(class_exists($notice_type_class))
		{
			return $this->add($args['id'], $args);
		}

		return false;
	}

	/**
	 * @param array $args
	 *
	 * @return void
	 */
	public function output($args = [])
	{
		$notices = $this->notices;

		if($notices && count($notices) > 0)
		{
			foreach($notices as $notice_key => $notice_args)
			{
				if(empty($notice_key) || !$this->is_context($notice_args))
				{
					continue;
				}

				$notice_class = InfoNotice::class;

				if(isset($this->notices_types[$notice_args['type']]))
				{
					$notice_class = $this->notices_types[$notice_args['type']];
				}

				$notice = new $notice_class($notice_args);

				$notice->output(true);

				if($notice->isDismissible())
				{
					$this->output_scripts($notice->getId());
				}
				else
				{
					$this->remove($notice->getId());
				}
			}
		}
	}

	/**
	 * @param $notice_id
	 *
	 * @return void
	 */
	protected function output_scripts($notice_id)
	{
		$nonce = wp_create_nonce( $this->getTransientName() . '_' . $notice_id);
		?>
		<script>
            jQuery(document).ready(function($)
            {
                // Dismiss notice
                function dismissNotice(dismissElement)
                {
                    var container = dismissElement.closest('.notice');
                    container.fadeTo( 100, 0, function()
                    {
                        container.slideUp( 100, function()
                        {
                            container.remove();
                        });
                    });
                }

                jQuery( '#notice-<?php echo esc_attr($notice_id); ?>.notice-dismiss').click(function()
                {
                    var data =
                    {
                        id: '<?php echo esc_attr(rawurlencode($notice_id)); ?>',
                        action: '<?php echo esc_attr(rawurlencode($this->getTransientName())); ?>',
                        _nonce: '<?php echo esc_html($nonce); ?>',
                    };

                    jQuery.post(ajaxurl, data, function(response)
                    {
                        dismissNotice(jQuery('#<?php echo esc_attr($notice_id); ?>.notice'));
                    });
                });
            });
		</script>
		<?php
	}

	/**
	 * Evaluate if we're on the right place depending on the "user_ids" argument.
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	public function is_users($args)
	{
		// If user ids is empty we want this shown on all users.
		if(!isset($args['user_ids']))
		{
			return true;
		}

		/**
		 * @var WP_User $current_user
		 */
		$current_user = wp_get_current_user();

		// Per user
		if(!is_array($args['user_ids']) && $current_user->ID === $args['user_ids'])
		{
			return true;
		}

		if(count($args['user_ids']) < 1)
		{
			return true;
		}

		// Check if we're on one of the defined users.
		return (in_array($current_user->ID, $args['user_ids'], true));
	}

	/**
	 * @param $args
	 *
	 * @return bool
	 */
	public function is_capabilities($args)
	{
		// If capability ids is empty we want this shown on all capabilities.
		if(!isset($args['capabilities']))
		{
			return true;
		}

		// Per cap
		if(!is_array($args['capabilities']) && current_user_can($args['capabilities']))
		{
			return true;
		}

		if(count($args['capabilities']) < 1)
		{
			return true;
		}

		// todo: array cap

		return true;
	}

	/**
	 * @param $args
	 *
	 * @return bool
	 */
	public function is_posts($args)
	{
		global $post;

		// If user ids is empty we want this shown on all users.
		if(!isset($args['post_ids']))
		{
			return true;
		}

		$post_id = is_object($post) ? $post->ID : 0;

		// Per post
		if(!is_array($args['post_ids']) && $post_id === $args['post_ids'])
		{
			return true;
		}

		if(count($args['post_ids']) < 1)
		{
			return true;
		}

		// Check if we're on one of the defined posts.
		return (in_array($post_id, $args['post_ids'], true));
	}

	/**
	 * Check context for notices show
	 *
	 * @param $notice_args
	 *
	 * @return bool
	 */
	public function is_context($notice_args)
	{
		/**
		 * Screen
		 */
		if(false === $this->is_screen($notice_args['context']))
		{
			return false;
		}

		/**
		 * User
		 */
		if(false === $this->is_users($notice_args['context']))
		{
			return false;
		}

		/**
		 * Capability
		 */
		if(false === $this->is_capabilities($notice_args['context']))
		{
			return false;
		}

		/**
		 * Post
		 */
		if(false === $this->is_posts($notice_args['context']))
		{
			return false;
		}

		return true;
	}

	/**
	 * Evaluate if we're on the right place depending on the "screen_ids" argument.
	 *
	 * @return bool
	 */
	private function is_screen($args)
	{
		// If screen is empty we want this shown on all screens.
		if(!isset($args['screen_ids']))
		{
			return true;
		}

		// Make sure the get_current_screen function exists.
		if(!function_exists('get_current_screen'))
		{
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}

		/**
		 * @var WP_Screen $current_screen
		 */
		$current_screen = get_current_screen();

		// Per screen
		if(!is_array($args['screen_ids']) && $current_screen->id === $args['screen_ids'])
		{
			return true;
		}

		if(count($args['screen_ids']) < 1)
		{
			return true;
		}

		// Check if we're on one of the defined screens.
		return (in_array($current_screen->id, $args['screen_ids'], true));
	}

	/**
	 * Remove a single notice from the queue by id
	 *
	 * @param string $key
	 */
	public function remove(string $key)
	{
		if($this->has($key))
		{
			unset($this->notices[$key]);
		}

		return true;
	}

	/**
	 * Clean all notices in buffer
	 *
	 * @return void
	 */
	public function purge()
	{
		$this->notices = [];
	}

	/**
	 * Handle ajax requests
	 */
	public function ajaxHandler()
	{
		// Sanity check: Early exit if we're not on ajax action.
		if(!isset($_POST['action']) || $this->getTransientName() !== $_POST['action'])
		{
			return;
		}

		// Sanity check: Early exit if the ID of the notice is not the one from this object.
		if(!isset($_POST['id']) || !$this->has($_POST['id']))
		{
			return;
		}

		$notice_id = $_POST['id'];

		// Security check: Make sure nonce is OK.
		check_ajax_referer($this->getTransientName() . '_' . $notice_id, '_nonce', true);

		// If we got this far, we need to dismiss the notice.
		$this->dismissible($notice_id);

		wp_send_json_success(array('notice_id' => $notice_id));
	}

	/**
	 * @param $notice_id
	 *
	 * @return void
	 */
	protected function dismissible($notice_id)
	{
		$this->remove($notice_id);
	}

	/**
	 * @return bool
	 */
	public function isUseAdminNotices(): bool
	{
		return $this->use_admin_notices;
	}

	/**
	 * @param bool $use_admin_notices
	 */
	public function setUseAdminNotices(bool $use_admin_notices)
	{
		$this->use_admin_notices = $use_admin_notices;
	}

	/**
	 * @return bool
	 */
	public function isUseAllAdminNotices(): bool
	{
		return $this->use_all_admin_notices;
	}

	/**
	 * @param bool $use_all_admin_notices
	 */
	public function setUseAllAdminNotices(bool $use_all_admin_notices)
	{
		$this->use_all_admin_notices = $use_all_admin_notices;
	}

	/**
	 * @return bool
	 */
	public function isUseNetworkAdminNotices(): bool
	{
		return $this->use_network_admin_notices;
	}

	/**
	 * @param bool $use_network_admin_notices
	 */
	public function setUseNetworkAdminNotices(bool $use_network_admin_notices)
	{
		$this->use_network_admin_notices = $use_network_admin_notices;
	}

	/**
	 * @return bool
	 */
	public function isUseUserAdminNotices(): bool
	{
		return $this->use_user_admin_notices;
	}

	/**
	 * @param bool $use_user_admin_notices
	 */
	public function setUseUserAdminNotices(bool $use_user_admin_notices)
	{
		$this->use_user_admin_notices = $use_user_admin_notices;
	}

	/**
	 * Добавление нового типа для уведомлений
	 *
	 * @param string $name Наименование типа
	 * @param callable $class Наименование класса реализующего абстрактный класс NoticeAbstract
	 *
	 * @return true
	 * @throws Exception
	 */
	public function registerType(string $name, $class): bool
	{
		if(empty($name))
		{
			throw new InvalidArgumentException('Type is empty');
		}

		if(empty($class))
		{
			throw new InvalidArgumentException('Class is empty');
		}

		if(isset($this->notices_types[$name]))
		{
			throw new Exception('Notice name is exists');
		}

		$this->notices_types[$name] = $class;

		return true;
	}

	/**
	 * Saving all notices from buffer
	 *
	 * @return bool
	 */
	public function save(): bool
	{
		if($this->count())
		{
			return set_transient($this->getTransientName(), $this->notices, $this->getTransientExpiration());
		}

		return $this->delete();
	}

	/**
	 * Get notices from storage
	 */
	public function fetch()
	{
		return get_transient($this->getTransientName());
	}

	/**
	 * Delete all notices in storage
	 *
	 * @return bool
	 */
	public function delete(): bool
	{
		return delete_transient($this->getTransientName());
	}

	/**
	 * Check if a notice exists in the queue
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has($key)
	{
		return array_key_exists($key, $this->notices);
	}

	/**
	 * Count number of notices in the queue
	 */
	public function count(): int
	{
		return count($this->notices);
	}

	/**
	 * Manager destructor.
	 */
	public function __destruct()
	{
		if($this->isAutoSave())
		{
			$this->save();
		}
	}

	/**
	 * Get array iterator for notices
	 *
	 * @return ArrayIterator
	 */
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->notices);
	}
}
<?php namespace Wc1c\Main\Admin\Wizards\Setup;

defined('ABSPATH') || exit;

use Wc1c\Main\Admin\Wizards\StepAbstract;
use Wc1c\Main\Traits\SingletonTrait;

/**
 * Database
 *
 * @package Wc1c\Main\Admin\Wizards
 */
class Database extends StepAbstract
{
	use SingletonTrait;

	/**
	 * Database constructor.
	 */
	public function __construct()
	{
		$this->setId('database');
	}

	/**
	 * Precessing step
	 */
	public function process()
	{
		if(isset($_POST['_wc1c-admin-nonce']))
		{
			if(wp_verify_nonce($_POST['_wc1c-admin-nonce'], 'wc1c-admin-wizard-database'))
			{
				$this->tablesInstall();
				wp_safe_redirect($this->wizard()->getNextStepLink());
				die;
			}

			wc1c()->admin()->notices()->create
			(
				[
					'type' => 'error',
					'data' => __('Create tables error. Please retry.', 'wc1c-main')
				]
			);
		}

		add_action('wc1c_wizard_content_output', [$this, 'output'], 10);
	}

	/**
	 * Output wizard content
	 *
	 * @return void
	 */
	public function output()
	{
		$args =
		[
			'step' => $this
		];

		wc1c()->views()->getView('wizards/steps/database.php', $args);
	}

	/**
	 * Install db tables
	 *
	 * @return bool
	 */
	public function tablesInstall()
	{
		$wc1c_version_database = 1;

		$current_db = get_site_option('wc1c_version_database', false);

		if($current_db === $wc1c_version_database)
		{
			return false;
		}

		$charset_collate = wc1c()->database()->get_charset_collate();
		$table_name = wc1c()->database()->base_prefix . 'wc1c';
		$table_name_meta = wc1c()->database()->base_prefix . 'wc1c_meta';

		$sql = "CREATE TABLE $table_name (
		`configuration_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`site_id` INT(11) UNSIGNED NULL DEFAULT NULL,
		`user_id` INT(11) UNSIGNED NULL DEFAULT NULL,
		`name` VARCHAR(155) NULL DEFAULT NULL,
		`status` VARCHAR(50) NULL DEFAULT NULL,
		`options` TEXT NULL DEFAULT NULL,
		`schema` VARCHAR(50) NULL DEFAULT NULL,
		`date_create` VARCHAR(50) NULL DEFAULT NULL,
		`date_modify` VARCHAR(50) NULL DEFAULT NULL,
		`date_activity` VARCHAR(50) NULL DEFAULT NULL,
		`wc1c_version` VARCHAR(50) NULL DEFAULT NULL,
		`wc1c_version_init` VARCHAR(50) NULL DEFAULT NULL,
		`schema_version` VARCHAR(50) NULL DEFAULT NULL,
		`schema_version_init` VARCHAR(50) NULL DEFAULT NULL,
		PRIMARY KEY (`configuration_id`),
		UNIQUE INDEX `configuration_id` (`configuration_id`)
		) $charset_collate;";

		$sql_meta = "CREATE TABLE $table_name_meta (
		`meta_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
		`configuration_id` BIGINT(20) NULL DEFAULT NULL,
		`name` VARCHAR(90) NULL DEFAULT NULL,
		`value` LONGTEXT NULL DEFAULT NULL,
		PRIMARY KEY (`meta_id`),
		UNIQUE INDEX `meta_id` (`meta_id`)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sql);
		dbDelta($sql_meta);

		add_site_option('wc1c_version_database', $wc1c_version_database);

		return true;
	}
}
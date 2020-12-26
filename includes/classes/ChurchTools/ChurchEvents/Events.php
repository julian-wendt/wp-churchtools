<?php

/** @noinspection SqlNoDataSourceInspection */

declare(strict_types = 1);

namespace ChurchTools\ChurchEvents;

use ChurchTools;
use ChurchTools\ApiClient;
use RuntimeException;

/**
 * Class Events
 */
class Events
{
	public const CRON_NAME  = 'church_cal_import';
	public const TABLE_NAME = 'church_cal';
	
	private static $instance;
	
	private $params = [];
	
	public static function getInstance() : Events {
		if (self::$instance === null) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public function __construct() {
		$churchTools = ChurchTools::getInstance();
		
		$settings = $churchTools->getSettings();
		
		$this->params['category_ids'] = $settings['eventCategoryIds'];
		$this->params['from']         = $settings['eventLookupFromDay'];
		$this->params['to']           = $settings['eventLookupToDay'];
		
		if ($churchTools->hasAccessToken()) {
			$this->params = $churchTools->addAccessToken($this->params);
		}
		
		self::registerImportCronJob();
	}
	
	/**
	 * Activate
	 *
	 * Create database table and cron job.
	 * Also run a first import.
	 */
	public function activate() : void {
		$this->createTable();
		$this->addImportCronJobSchedule();
		
		// Run first time import
		$this->updateFromApi();
	}
	
	/**
	 * Deactivate
	 *
	 * Drop database table and remove cron job.
	 */
	public function deactivate() : void {
		$this->deleteTable();
		$this->removeImportCronJobSchedule();
	}
	
	/**
	 * Create Table
	 *
	 * Create database table with all columns.
	 */
	private function createTable() : void {
		global $wpdb;
		
		// Required for dbdelta
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			lastchanged bigint NOT NULL,
			title varchar(255) NOT NULL,
			startdate datetime NOT NULL,
			enddate datetime NOT NULL,
			contact varchar(255),
			link varchar(255),
			PRIMARY KEY  (id),
			KEY title_index (title),
			KEY startdate_index (startdate),
			KEY enddate_index (enddate)
		) $charset_collate;";
		
		dbDelta($sql);
		
		add_option('jal_db_version', '1.0');
	}
	
	/**
	 * Delete Table
	 *
	 * Drops the whole table and all entries.
	 */
	private function deleteTable() : void {
		global $wpdb;
		
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		
		$wpdb->query(
			"DROP TABLE $table_name;"
		);
	}
	
	private function registerImportCronJob() : void {
		add_filter('cron_schedules', [ChurchTools::getInstance(), 'extendCronSchedules'], 10);
		add_action(self::CRON_NAME, [$this, 'updateFromApi']);
	}
	
	private function addImportCronJobSchedule() : void {
		$nextSchedule = ChurchTools::getInstance()->roundCurrentTimeToQuarterHour(time());
		wp_schedule_event($nextSchedule, 'quarter-hourly', self::CRON_NAME);
	}
	
	private function removeImportCronJobSchedule() : void {
		wp_clear_scheduled_hook(self::CRON_NAME);
		remove_action(self::CRON_NAME, [$this, 'updateFromApi']);
	}
	
	/**
	 * Update from API
	 *
	 * Use the API client to receive events from ChurchTools. Received events will be parsed and
	 * stored in the database. Existing events from previous imports will be deleted afterwards.
	 */
	public function updateFromApi() : void {
		global $wpdb;
		
		$tableName = $wpdb->prefix . self::TABLE_NAME;
		$timestamp = time();
		
		$client = ApiClient::getInstance();
		$events = $client(ApiClient::API_CHURCH_CAL, 'getCalendarEvents', $this->params);
		
		if ($events['status'] !== 'success') {
			throw new RuntimeException('Error accessing CT API: ' . $events['data']);
		}
		
		foreach ($events['data'] as $json) {
			$event = Event::fromJson($json);
			$event->store();
		}
		
		$wpdb->query(
			"DELETE FROM $tableName WHERE lastchanged < '$timestamp';"
		);
	}
}
<?php

/** @noinspection SqlNoDataSourceInspection */

declare(strict_types = 1);

use ChurchTools\ChurchEvents\Events;

if ( ! defined('ABSPATH')) {
	exit;
}

/**
 * Class ChurchTools
 * @package ChurchTools
 */
class ChurchTools
{
	private static $instance;
	
	private static $settings;
	private static $tenant;
	private static $ignoreCert;
	
	public static function getInstance() : ChurchTools {
		if (self::$instance === null) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public function __construct() {
		self::$settings   = self::importSettings();
		self::$tenant     = self::$settings['tenantName'];
		self::$ignoreCert = self::ignoreCert(self::$settings['ignoreCert']);
	}
	
	/**
	 * Activate
	 *
	 * Create database table and cron job.
	 * Also run a first import.
	 */
	public function activate() : void {
		Events::getInstance()->activate();
	}
	
	/**
	 * Deactivate
	 *
	 * Drop database table and remove cron job.
	 */
	public function deactivate() : void {
		Events::getInstance()->deactivate();
	}
	
	private function importSettings() : array {
		$output = [];
		
		$file = dirname(__dir__, 2) . '/settings.json';
		$json = null;
		
		if (file_exists($file)) {
			$json = file_get_contents($file);
		}
		
		if ($json) {
			$output = json_decode($json, true);
		}
		
		return $output;
	}
	
	private function ignoreCert(bool $ignoreCert = null) : bool {
		return $ignoreCert === true;
	}
	
	/**
	 * Get Tenant
	 *
	 * Returns the ChurchTools tenant name.
	 *
	 * @return mixed
	 */
	public function getTenant() {
		return self::$tenant;
	}
	
	/**
	 * Use SSL
	 *
	 * Return to ignore SSL cert or not.
	 *
	 * @return mixed
	 */
	public function validateCert() {
		return self::$ignoreCert;
	}
	
	/**
	 * Has Access Token
	 *
	 * Check if the settings contain a token.
	 *
	 * @return bool
	 */
	public function hasAccessToken() : bool {
		if (array_key_exists('accessToken', self::$settings)) {
			/** @noinspection NestedPositiveIfStatementsInspection */
			if (empty(self::$settings['accessToken']) !== true) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Add Access Token
	 *
	 * Returns the passed array containing a new token item.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function addAccessToken(array $params) : array {
		return array_merge(
			$params, [
				'csrf-token' => self::$settings['accessToken']
			]
		);
	}
	
	/**
	 * Get Settings
	 *
	 * Return settings from settings.json
	 *
	 * @return array
	 */
	public function getSettings() : array {
		return self::$settings;
	}
	
	/**
	 * Round Current Time To Quarter Hour
	 *
	 * Rounds given time to the next quarter hour.
	 *
	 * @param $time
	 *
	 * @return int
	 */
	public function roundCurrentTimeToQuarterHour($time) : int {
		$prev = $time - ($time % 900);
		return $prev + 900;
	}
	
	/**
	 * Extend and return cron schedules with further interval.
	 *
	 * @param $schedules
	 *
	 * @return array
	 */
	public function extendCronSchedules($schedules) : array {
		$schedules['quarter-hourly'] = [
			'interval' => 900,
			'display'  => __('Every 15 Minutes')
		];
		
		return $schedules;
	}
}
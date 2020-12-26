<?php

declare(strict_types = 1);

namespace ChurchTools;

use ChurchTools;
use RuntimeException;

use function array_key_exists;
use function defined;

if ( ! defined('ABSPATH')) {
	exit;
}

/**
 * Class ApiClient
 *
 * @package ChurchTools
 */
class ApiClient
{
	public const API_CHURCH_CAL = 1;
	public const API_CHURCH_RESOURCE = 2;
	
	private const URL = 'https://%HOST%.church.tools/index.php?q=%s';
	
	private const API_KEY = [
		self::API_CHURCH_CAL      => 'churchcal/ajax',
		self::API_CHURCH_RESOURCE => 'churchresource/ajax'
	];
	
	private static $instance;
	
	/**
	 * @return ApiClient
	 */
	public static function getInstance() : ApiClient {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * @param int $api
	 * @param string $func
	 * @param array $params
	 *
	 * @return array
	 */
	public function __invoke(int $api, string $func, array $params) : array {
		return $this->call($api, $func, $params);
	}
	
	/**
	 * Call
	 *
	 * Execute API call and return results.
	 *
	 * @param int $api
	 * @param string $func
	 * @param array $params
	 *
	 * @return array
	 */
	public function call(int $api, string $func, array $params) : array {
		if ( ! array_key_exists($api, self::API_KEY)) {
			throw new RuntimeException('Invalid API number: ' . $api);
		}
		
		$url  = $this->buildUrl($api);
		$data = array_merge($params, ['func' => $func]);
		
		$result = $this->performRequest($url, $data);
		return json_decode($result, true);
	}
	
	private function buildUrl(int $api) : string {
		$tenant = ChurchTools::getInstance()->getTenant();
		$url    = str_replace('%HOST%', $tenant, self::URL);
		
		return sprintf($url, self::API_KEY[$api]);
	}
	
	private function getContext(array $data) {
		$options = [
			'http' => [
				'header'  => 'Content-Type: application/x-www-form-urlencoded',
				'method'  => 'POST',
				'content' => http_build_query($data)
			]
		];
		
		if (ChurchTools::getInstance()->validateCert() === true) {
			$options = array_merge(
				$options, [
					'ssl' => [
						'verify_peer'      => false,
						'verify_peer_name' => false
					]
				]
			);
		}
		
		return stream_context_create($options);
	}
	
	private function performRequest($url, $data) : string {
		$context = $this->getContext($data);
		return file_get_contents($url, false, $context);
	}
}
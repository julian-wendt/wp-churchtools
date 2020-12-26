<?php

/** @noinspection SqlNoDataSourceInspection */

declare(strict_types = 1);

namespace ChurchTools\ChurchEvents;

use DateTime;
use stdClass;

use function array_key_exists;
use function defined;

if ( ! defined('ABSPATH')) {
	exit;
}

/**
 * Class CalEvent
 *
 * @package ChurchTools
 */
class Event {
	private const REGEX_CONTACT = '/Kontakt: ([^\r\n]+)/i';
	
	private $id;
	private $title;
	private $startdate;
	private $enddate;
	private $contact;
	private $link;
	
	private function __construct(
		string $title,
		string $startdate,
		string $enddate,
		string $contact = null,
		string $link = null
	) {
		$this->title     = $title;
		$this->startdate = $startdate;
		$this->enddate   = $enddate;
		$this->contact   = $contact;
		$this->link      = $link;
	}
	
	/**
	 * Form JSON
	 *
	 * Uses the data received from the API formatted as JSON and returns an event object.
	 *
	 * @param array $json
	 *
	 * @return Event
	 */
	public static function fromJson(array $json) : Event {
		$titleParsed  = self::parseTitle($json['bezeichnung']);
		$notesParsed  = null;
		$linkFiltered = null;
		
		if (array_key_exists('notes', $json)) {
			$notesParsed = self::parseNotes($json['notes']);
		}
		
		if (array_key_exists('link', $json)) {
			$linkFiltered = self::filterLink($json['link']);
		}
		
		return new self(
			$titleParsed, $json['startdate'], $json['enddate'], $notesParsed['contact'], $linkFiltered
		);
	}
	
	private static function fromDb(stdClass $data) : Event {
		$event = new self(
			$data->title, $data->startdate, $data->enddate, $data->contact, $data->link
		);
		
		$event->id = +$data->id;
		
		return $event;
	}
	
	private static function parseTitle(string $title) : string {
		return trim($title);
	}
	
	private static function parseNotes(string $notes = null) : array {
		$matches = [];
		
		if ($notes) {
			preg_match(self::REGEX_CONTACT, $notes, $matches);
		}
		
		return [
			'contact' => $matches[1]
		];
	}
	
	private static function filterLink(string $link = null) : ?string {
		$filteredLink = null;
		
		if ($link) {
			$filteredLink = filter_var($link, FILTER_SANITIZE_URL);
		}
		
		return $filteredLink;
	}
	
	private static function tableName() : string {
		global $wpdb;
		
		return $wpdb->prefix . Events::TABLE_NAME;
	}
	
	/**
	 * Store
	 *
	 * Store event in database.
	 *
	 * @return bool
	 */
	public function store() : bool {
		global $wpdb;
		
		$table = self::tableName();
		
		$wpdb->insert(
			$table,
			[
				'lastchanged' => time(),
				'title'       => $this->title,
				'startdate'   => $this->startdate,
				'enddate'     => $this->enddate,
				'contact'     => $this->contact,
				'link'        => $this->link
			]
		);
		
		return true;
	}
	
	/**
	 * Query
	 *
	 * Get events from database.
	 *
	 * @param $startdate
	 * @param $enddate
	 *
	 * @return array|object|null
	 */
	public static function query($startdate, $enddate) {
		global $wpdb;
		
		$table_name = self::tableName();
		
		$time  = ' 00:00:00';
		$start = $startdate . $time;
		$end   = $enddate . $time;
		
		$query = "SELECT * FROM $table_name
				  WHERE startdate >= %s AND startdate <= %s
				  ORDER BY startdate";
		
		$result = $wpdb->get_results($wpdb->prepare($query, [$start, $end]));
		if ($result === null) {
			return null;
		}
		
		return array_map([self::class, 'fromDb'], $result);
	}
	
	public function id() : int {
		return $this->id;
	}
	
	public function title() : string {
		return $this->title;
	}
	
	public function startdate() : DateTime {
		return new DateTime($this->startdate);
	}
	
	public function enddate() : DateTime {
		return new DateTime($this->enddate);
	}
	
	public function contact() : ?string {
		return $this->contact;
	}
	
	public function link() : ?string {
		return $this->link;
	}
}
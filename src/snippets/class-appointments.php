<?php

declare(strict_types = 1);

use ChurchTools\ChurchEvents\Event;

if ( ! defined('ABSPATH')) {
	exit;
}

/**
 * Class Appointments
 */
class Appointments
{
	private const DATE_FORM = 'Y-m-d';
	private const FIRST_DAY = 'monday this week';
	private const LAST_DAY  = 'sunday this week';
	
	private const DATE_FORMAT_SUBSTITUTON = [
		'd' => '%d',
		'm' => '%m',
		'Y' => '%Y',
		'l' => '%A',
		'F' => '%B',
		'H' => '%H',
		'i' => '%M'
	];
	
	private static $instance;
	
	public static function getInstance() : Appointments {
		if (self::$instance === null) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 * Get Content
	 *
	 * Query and return events for a defined amount of weeks from the database.
	 * Returned events are grouped by week and day and structured via HTML.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getContent() : string {
		if ( ! class_exists(ChurchTools::class)) {
			return 'The ChurchTools Plugin is not available.';
		}
		
		$events = $this->getEvents();
		return self::output($events);
	}
	
	private function getEvents(int $weeks = 2) : array {
		$events = [];
		
		for ($i = 0; $i < $weeks; ++$i) {
			$from     = $this->getFirstWeekDay($i);
			$to       = $this->getLastWeekDay($i);
			$events[] = Event::query($from, $to);
		}
		
		return $events;
	}
	
	private static function compare($var1, $var2) : int {
		$out = 0;
		
		if ($var1 > $var2) {
			$out = 1;
		}
		
		if ($var1 < $var2) {
			$out = -1;
		}
		
		return $out;
	}
	
	private static function groupDaily(array $events) : array {
		$grouped = [];
		
		foreach ($events as $event) {
			$date = $event->startdate()->format('d.m.Y');
			
			if ( ! array_key_exists($date, $grouped)) {
				$grouped[$date] = [];
			}
			
			$grouped[$date][] = $event;
		}
		
		foreach ($grouped as &$items) {
			uasort(
				$items, static function(Event $event1, Event $event2) {
				if (($result = self::compare($event1->startdate(), $event2->startdate())) !== 0) {
					return $result;
				}
				
				return self::compare($event1->title(), $event2->title());
			}
			);
		}
		unset($items);
		
		uksort(
			$grouped, static function($date1, $date2) {
			return self::compare(new DateTime($date1), new DateTime($date2));
		}
		);
		
		return $grouped;
	}
	
	private static function formatDate(DateTime $date, string $format) : string {
		setlocale(LC_TIME, 'de_DE');
		
		$format = str_replace(
			array_keys(self::DATE_FORMAT_SUBSTITUTON),
			array_values(self::DATE_FORMAT_SUBSTITUTON),
			$format
		);
		
		return strftime($format, $date->getTimestamp());
	}
	
	private static function output(array $events) : string {
		$out = '';
		
		$out .= '<div class="appointment-list">';
		
		foreach ($events as $week) {
			
			$out .= '<div class="week">';
			
			if ($week === null) {
				$out .= '<div class="appointment-empty">Keine Termine gefunden</div>';
			} else {
				foreach (self::groupDaily($week) as $date => $day) {
					$date = new DateTime($date);
					
					$out .= self::dayHeader($date);
					
					foreach ($day as $event) {
						$out .= self::event($event);
					}
					
					$out .= self::dayFooter();
				}
			}
			
			$out .= '</div>';
		}
		
		$out .= '</div>';
		
		return $out;
	}
	
	private static function dayHeader(DateTime $day) : string {
		$out = '';
		
		$out .= '<div class="day">';
		
		$out .= '<div class="date">';
		$out .= '<div class="day-name">' . self::formatDate($day, 'l') . '</div>';
		$out .= '<div class="day-date">' . self::formatDate($day, 'd. F') . '</div>';
		$out .= '</div>';
		
		$out .= '<div class="entries">';
		
		return $out;
	}
	
	private static function event(Event $event) : string {
		$out = '';
		
		$out .= '<div class="entry" id="event-' . $event->id() . '">';
		
		/* Return event start time */
		$out .= '<div class="time">' . self::formatDate($event->startdate(), 'H:i') . '</div>';
		$out .= '<div class="details">';
		
		/* Return event title */
		$out .= '<div class="title">' . $event->title() . '</div>';
		$out .= '</div>';
		
		$out .= '</div>';
		
		return $out;
	}
	
	private static function dayFooter() : string {
		$out = '';
		
		// Close appointment-container
		$out .= '</div>';
		
		// Close appointment-day
		$out .= '</div>';
		
		return $out;
	}
	
	private function getFirstWeekDay(int $addWeeks) : ?string {
		if ($addWeeks === 0) {
			// Don't display events before today
			return date(self::DATE_FORM);
		}
		
		$addDays = $addWeeks * 7 . ' days';
		
		return date(self::DATE_FORM, strtotime($addDays, strtotime(self::FIRST_DAY)));
	}
	
	private function getLastWeekDay(int $addWeeks) {
		$addDays = ($addWeeks * 7) + 1 . ' days';
		return date(self::DATE_FORM, strtotime($addDays, strtotime(self::LAST_DAY)));
	}
}
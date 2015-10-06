<?php

namespace Eu\IctCollege\Schedule;

use \Eu\IctCollege\Schedule\Models\ParsedSchedule;

class Diff {

	public function diff(ParsedSchedule $a, ParsedSchedule $b) {
		$aTimeBased = $this->scheduleToTimeArray($a->schedule);
		$bTimeBased = $this->scheduleToTimeArray($b->schedule);
		foreach ($aTimeBased as $day => $dayData) {
			for ($period = 1; $period <= 8; $period++) {
				//print_r($day[$period]);
				if (isset($dayData[$period])) {
					foreach ($dayData[$period] as $entry) {
						if (in_array($entry, $bTimeBased[$day][$period])) {
							print_r($entry);
						}
						//print_r($entry);
					}
				}
			}
		}
		//print_r($this->scheduleToTimeArray($b->schedule));
		
	}

	private function scheduleToTimeArray(array $scheduleEntries) {
		$timeBased = array();

		foreach ($scheduleEntries as $entry) {
			if (!isset($timeBased[$entry->day])) {
				$timeBased[$entry->day] = array();
			}
			if (!isset($timeBased[$entry->day][$entry->period])) {
				$timeBased[$entry->day][$entry->period] = array();
			}
			$timeBased[$entry->day][$entry->period][] = $entry;
		}
		
		return $timeBased;
	}

}

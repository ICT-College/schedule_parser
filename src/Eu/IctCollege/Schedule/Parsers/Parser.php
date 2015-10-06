<?php

namespace Eu\IctCollege\Schedule\Parsers;

use \Eu\IctCollege\Schedule\Models\ScheduleEntry;

abstract class Parser {

	protected function parseUnit(array $units, $unitParser) {
		$parsed = array();
		foreach ($units as $unit) {
			$data = $this->{$unitParser}($unit);
			$parsed[$data->id] = $data;	
		}
		return $parsed;
	}

	final protected function parseScheduleEntries($entries, array $teachers, array $classes, array $subjects, $classrooms) {
		foreach ($entries as &$entry) {
			$entry->class = (isset($classes[$entry->class])) ? $classes[$entry->class] : null;
			$entry->teacher = (isset($teachers[$entry->teacher])) ? $teachers[$entry->teacher] : null;
			$entry->subject = (isset($subjects[$entry->subject])) ? $subjects[$entry->subject] : null;
			$entry->classroom = (isset($classrooms[$entry->classroom])) ? $classrooms[$entry->classroom] : null;
		}
		return $entries;
	}

	protected abstract function parseTeacher($unit);
	protected abstract function parseClass($unit);
	protected abstract function parseSubject($unit);
	protected abstract function parseClassroom($unit);
	protected abstract function parseScheduleEntry($unit);

}

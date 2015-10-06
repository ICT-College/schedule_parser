<?php

namespace Eu\IctCollege\Schedule\Parsers;

use \Eu\IctCollege\Schedule\Models\Teacher;
use \Eu\IctCollege\Schedule\Models\ClassEntry;
use \Eu\IctCollege\Schedule\Models\Subject;
use \Eu\IctCollege\Schedule\Models\Classroom;
use \Eu\IctCollege\Schedule\Models\ScheduleEntry;
use \Eu\IctCollege\Schedule\Models\ParsedSchedule;

class RstParser extends Parser {

	public function parse($contents, $year, $week) {
		$dataArrays = $this->convertToArrays($contents);

		return $this->generateModels($dataArrays, $year, $week);
	}

	private function convertToArrays($payload) {
		$stack = new \SplStack();

		$lines = explode("\n", $payload);

		foreach ($lines as $line) {
			$line = trim($line);
			$parts = explode(';', $line);

			if (substr($parts[0], 0, 5) == 'BEGIN') {
				//echo 'Begin ' . $parts[1] . PHP_EOL;
				$data[$parts[1]] = array();
				$stack->push($parts[1]);
			} elseif (substr($parts[0], 0, 3) == 'END') {
				//echo 'Eind ' . $parts[1] . PHP_EOL;
				$closedStackItem = $stack->pop();
				$data[$stack->top()][$closedStackItem][] = $data[$closedStackItem];
				unset($data[$closedStackItem]);
			} else {
				$data[$stack->top()][] = $parts;
			}
		}
		
		return $data;
	}


	protected function generateModels(array $dataArrays, $year, $week) {
		$teachers = $this->parseUnit($dataArrays['ROOSTERS']['DOCENTEN'][0], 'parseTeacher');
		$classes = $this->parseUnit($dataArrays['ROOSTERS']['KLASSEN'][0], 'parseClass');
		$subjects = $this->parseUnit($dataArrays['ROOSTERS']['VAKKEN'][0], 'parseSubject');
		$classrooms = $this->parseUnit($dataArrays['ROOSTERS']['LOKALEN'][0], 'parseClassroom');
		$tydCollection = $dataArrays['ROOSTERS']['ROOSTER'][0]['TYD'];
		
		$schedule = array();

		$i = 0;
		foreach ($tydCollection as $tyd) {
			foreach ($tyd as $unit) {
				$scheduleEntry = array();
				$scheduleEntry['id'] = $i;
				$scheduleEntry['date'] = array(
					'year' => $year,
					'week' => $week,
					'day' => $unit[0]
				);
				$scheduleEntry['period'] = $unit[1];
				$scheduleEntry['class'] = $unit[3];
				$scheduleEntry['teacher'] = $unit[2];
				$scheduleEntry['subject'] = $unit[5];
				$scheduleEntry['classroom'] = $unit[4];
				$schedule[] = $scheduleEntry;
				++$i;
			}
		}
		$scheduleEntries = $this->parseUnit($schedule, 'parseScheduleEntry');
		
		$parsed = new ParsedSchedule();
		$parsed->teachers = $teachers;
		$parsed->classes = $classes;
		$parsed->subjects = $subjects;
		$parsed->classrooms = $classrooms;
		$parsed->schedule = $this->parseScheduleEntries($scheduleEntries, $teachers, $classes, $subjects, $classrooms);
		
		return $parsed;
	}

	protected function parseTeacher($unit) {
		$parsed = new Teacher();
		$parsed->id = $unit[2];
		$parsed->abbreviation = $unit[0];
		$parsed->name = $unit[1];

		return $parsed;
	} 

	protected function parseClass($unit) {
		$parsed = new ClassEntry();
		$parsed->id   = $unit[2];
		$parsed->name = $unit[0];

		return $parsed;
	}

	protected function parseSubject($unit) {
		$parsed = new Subject();
		$parsed->id = $unit[1];
		$parsed->abbreviation = $unit[0];

		return $parsed;
	}

	protected function parseClassroom($unit) {
		$parsed = new Classroom();
		$parsed->id = $unit[2];
		$parsed->code = $unit[0];

		return $parsed;
	}

	protected function parseScheduleEntry($unit) {
		$parsed = new ScheduleEntry();
		$parsed->id = $unit['id'];
		$parsed->day = strtotime($unit['date']['year'] . 'W' . str_pad($unit['date']['week'], 2, '0', STR_PAD_LEFT) . $unit['date']['day']);
		$parsed->period = $unit['period'] - 1;
		$parsed->class = $unit['class'];
		$parsed->teacher = $unit['teacher'];
		$parsed->subject = $unit['subject'];
		$parsed->classroom = $unit['classroom'];

		return $parsed;
	}

}

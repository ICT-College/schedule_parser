<?php

namespace Eu\IctCollege\Schedule\Parsers;

use \Eu\IctCollege\Schedule\Models\Teacher;
use \Eu\IctCollege\Schedule\Models\ClassEntry;
use \Eu\IctCollege\Schedule\Models\Subject;
use \Eu\IctCollege\Schedule\Models\Classroom;
use \Eu\IctCollege\Schedule\Models\ScheduleEntry;
use \Eu\IctCollege\Schedule\Models\ParsedSchedule;
use \Eu\IctCollege\Schedule\Readers\Database\Output;

class DatabaseParser extends Parser {

	public function parse(Output $output) {
		$teachers = $this->parseUnit($output->teachers, 'parseTeacher');
		$classes = $this->parseUnit($output->classes, 'parseClass');
		$subjects = $this->parseUnit($output->subjects, 'parseSubject');
		$classrooms = $this->parseUnit($output->classrooms, 'parseClassroom');

		$scheduleEntries = $this->parseUnit($output->scheduleEntries, 'parseScheduleEntry');

		//print_r();

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
		$parsed->id = $unit['id'];
		$parsed->abbreviation = $unit['abbreviation'];
		$parsed->name = $unit['name'];

		return $parsed;
	} 

	protected function parseClass($unit) {
		$parsed = new ClassEntry();
		$parsed->id = $unit['id'];
		$parsed->name = $unit['name'];

		return $parsed;
	}

	protected function parseSubject($unit) {
		$parsed = new Subject();
		$parsed->id = $unit['id'];
		$parsed->abbreviation = $unit['abbreviation'];

		return $parsed;
	}

	protected function parseClassroom($unit) {
		$parsed = new Classroom();
		$parsed->id = $unit['id'];
		$parsed->code = $unit['code'];

		return $parsed;
	}

	protected function parseScheduleEntry($unit) {
		$parsed = new ScheduleEntry();
		$parsed->id = $unit['id'];
		$parsed->day = $unit['day'];
		$parsed->period = $unit['period'] - 1;
		$parsed->class = $unit['class'];
		$parsed->teacher = $unit['teacher'];
		$parsed->subject = $unit['subject'];
		$parsed->classroom = $unit['classroom'];

		return $parsed;
	}

}

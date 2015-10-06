<?php

namespace Eu\IctCollege\Schedule\Models;

use \Eu\IctCollege\Schedule\Models\Period;

class ScheduleEntry {

	public function __construct($date, Period $period, $length, $class, $subject, $classroom, $teacher = null) {
		$this->date = $date;
		$this->period = $period;
		$this->length = $length;
		$this->class = $class;
		$this->subject = $subject;
		$this->classroom = $classroom;
		$this->teacher = $teacher;
	}

	public $teacher;
	public $class;
	public $subject;

}

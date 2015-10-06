<?php

namespace Eu\IctCollege\Schedule\Models;

class ParsedSchedule {

	public function setTeachers(array $teachers) {
		$this->teachers = $teachers;
	}

	public function getTeachers() {
		return $this->teachers;
	}

	public function setClassrooms(array $classrooms) {
		$this->classrooms = $classrooms;
	}

	public function getClassrooms() {
		return $this->classrooms;
	}

	public function setSubjects(array $subjects) {
		$this->subjects = $subjects;
	}

	public function getSubjects() {
		return $this->subjects;
	}

	public function setClasses(array $classes) {
		$this->classes = $classes;
	}

	public function getClasses() {
		return $this->classes;
	}

	public function setPeriods(array $periods) {
		$this->periods = $periods;
	}

	public function getPeriods() {
		return $this->periods;
	}

	public function setScheduleEntries(array $scheduleEntries) {
		$this->scheduleEntries = $scheduleEntries;
	}

	public function getScheduleEntries() {
		return $this->scheduleEntries;
	}

}

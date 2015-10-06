<?php

namespace Eu\IctCollege\Schedule\Readers;

class DatabaseReader {

	private $db;

	const SELECT_SCHEDULE_ENTRIES = <<<'EOD'
		SELECT
			sched.id AS id,
			teacher_id AS teacher,
			class_id AS class,
			subject_id AS subject,
			classroom_id AS classroom,
			UNIX_TIMESTAMP(date) AS day,
			period AS period
		FROM `schedules_class` sched
		WHERE
			WEEK(date, 3) = :week &&
			YEAR(date) = :year
EOD;
	const SELECT_SCHEDULE_ENTRIES_OLD = <<<'EOD'
		SELECT
			sched.id AS id,
			teacher.abbreviation AS teacher,
			class.name AS class,
			subject.abbreviation AS subject,
			classroom.code AS classroom,
			IF(WEEKDAY(date) = 0, 1, WEEKDAY(date) + 1) AS day,
			period AS period
		FROM `schedules_class` sched
		LEFT JOIN teachers teacher ON (teacher.id = teacher_id)
		JOIN classes class ON (class.id = class_id)
		JOIN subjects subject ON (subject.id = subject_id)
		JOIN classrooms classroom ON (classroom.id = classroom_id)
		WHERE
			WEEK(date, 3) = :week &&
			YEAR(date) = :year
EOD;


	public function __construct($db) {
		$this->db = $db;
	}

	public function read($year, $week) {
		$output = new Database\Output();

		$statementTeachers = $this->db->prepare("SELECT id, abbreviation, name FROM teachers");
		$statementTeachers->execute();

		$output->teachers = $statementTeachers->fetchAll();

		$statementClasses = $this->db->prepare("SELECT id, name FROM classes");
		$statementClasses->execute();

		$output->classes = $statementClasses->fetchAll();

		$statementSubject = $this->db->prepare("SELECT id, abbreviation FROM subjects");
		$statementSubject->execute();

		$output->subjects = $statementSubject->fetchAll();

		$statementClassroom = $this->db->prepare("SELECT id, code FROM classrooms");
		$statementClassroom->execute();

		$output->classrooms = $statementClassroom->fetchAll();

		$statementScheduleEntries = $this->db->prepare(self::SELECT_SCHEDULE_ENTRIES);
		$statementScheduleEntries->execute(array(
			'year'	=> $year,
			'week'	=> $week
		));

		$output->scheduleEntries = $statementScheduleEntries->fetchAll();

		return $output;
	}

}

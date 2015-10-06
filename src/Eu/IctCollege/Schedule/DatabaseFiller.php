<?php

namespace Eu\IctCollege\Schedule;

use \Eu\IctCollege\Schedule\Models\ParsedSchedule;

use \Eu\IctCollege\Schedule\Models\Period;

class DatabaseFiller {

	private $db;

	const DELETE_DATA = <<<'EOD'
		DELETE FROM schedule_entries WHERE department_id = :department_id AND date BETWEEN :start AND :end;
EOD;

	const UPDATE_TEACHER_QUERY = <<<'EOD'
		INSERT INTO `teachers` (`department_id`, `abbreviation`, `name`) VALUES (:department_id, :abbreviation_insert, :name_insert) ON DUPLICATE KEY UPDATE abbreviation = :abbreviation_update, name = :name_update;
EOD;

	const SELECT_TEACHERS = <<<'EOD'
		SELECT id, abbreviation FROM teachers WHERE department_id = :department_id;
EOD;

	const UPDATE_CLASSROOM_QUERY = <<<'EOD'
		INSERT INTO `classrooms` (`department_id`, `code`) VALUES (:department_id, :code_insert) ON DUPLICATE KEY UPDATE code = :code_update;
EOD;

	const SELECT_CLASSROOMS = <<<'EOD'
		SELECT id, code FROM classrooms WHERE department_id = :department_id;
EOD;

	const UPDATE_SUBJECT_QUERY = <<<'EOD'
		INSERT INTO `subjects` (`department_id`, `abbreviation`) VALUES (:department_id, :abbreviation_insert) ON DUPLICATE KEY UPDATE abbreviation = :abbreviation_update;
EOD;

	const SELECT_SUBJECTS = <<<'EOD'
		SELECT id, abbreviation FROM subjects WHERE department_id = :department_id;
EOD;

	const UPDATE_CLASS_QUERY = <<<'EOD'
		INSERT INTO `classes` (`department_id`, `name`) VALUES (:department_id, :name_insert) ON DUPLICATE KEY UPDATE name = :name_update;
EOD;

	const SELECT_CLASSES = <<<'EOD'
		SELECT id, name FROM classes WHERE department_id = :department_id;
EOD;
	
	const UPDATE_PERIOD_QUERY = <<<'EOD'
		INSERT INTO `periods` (`department_id`, `period`, `start`, `end`, `title`) VALUES (:department_id, :period_insert, :start_insert, :end_insert, :title_insert) ON DUPLICATE KEY UPDATE period = :period_update, start = :start_update, end = :end_update, title = :title_update;
EOD;

	const SELECT_PERIODS = <<<'EOD'
		SELECT id, period, start, end, title FROM periods WHERE department_id = :department_id;
EOD;

	const UPDATE_SCHEDULE_ENTRY = <<<'EOD'
		INSERT INTO `schedule_entries` (`department_id`, `date`, `class_id`, `period`, `subject_id`, `teacher_id`, `classroom_id`) VALUES (:department_id, :date_insert, :class_id_insert, :period_insert, :subject_id_insert, :teacher_id_insert, :classroom_id_insert) ON DUPLICATE KEY UPDATE subject_id = :subject_id_update, teacher_id = :teacher_id_update, classroom_id = :classroom_id_update;
EOD;

	public function __construct(\PDO $db, $departmentId) {
		$this->db = $db;
		$this->departmentId = $departmentId;
	}

	public function fill(ParsedSchedule $parsed) {
		$scheduleEntries = array();

		$scheduleEntries = $parsed->getScheduleEntries();		
		$teachers = $parsed->getTeachers();
		$classrooms = $parsed->getClassrooms();
		$subjects = $parsed->getSubjects();
		$classes = $parsed->getClasses();
		$periods = $parsed->getPeriods();

		$start = 99999999999999999;
		$end = 0;
	
		foreach ($parsed->getScheduleEntries() as $scheduleEntry) {
			$entry = array();
			if ($start > $scheduleEntry->date) {
				$start = $scheduleEntry->date;
			}
			if ($end < $scheduleEntry->date) {
				$end = $scheduleEntry->date;
			}
		}

		$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$this->db->beginTransaction();

		$deleteScheduleEntries = $this->db->prepare(DatabaseFiller::DELETE_DATA);
		$parameters = array(
			'department_id' => $this->departmentId,
			'start' => date('Y-m-d', $start),
			'end' => date('Y-m-d', $end),
		);
		$deleteScheduleEntries->execute($parameters);
		$deleteScheduleEntries->closeCursor();
		$deleteScheduleEntries->fetchAll();

		$teacherUpdate = $this->db->prepare(DatabaseFiller::UPDATE_TEACHER_QUERY);
		foreach ($teachers as $teacher) {
			$parameters = array(
				'department_id' => $this->departmentId,
				'abbreviation_insert' => $teacher->abbreviation,
				'name_insert' => $teacher->name,
				'abbreviation_update' => $teacher->abbreviation,
				'name_update' => $teacher->name,
			);
			$teacherUpdate->execute($parameters);
		}

		$teachersSelect = $this->db->prepare(DatabaseFiller::SELECT_TEACHERS);
		$parameters = array(
			'department_id' => $this->departmentId,
		);
		$teachersSelect->execute($parameters);
		$teacherRows = $teachersSelect->fetchAll();
		foreach ($teacherRows as $row) {
			foreach ($teachers as $index => $teacher) {
				if ($row['abbreviation'] == $teacher->abbreviation) {
					$teacher->id = $row['id'];
					$teachers[$teacher->abbreviation] = $teacher;
					unset($teachers[$index]);
				}
			}
		}
		print_r($teachers);

		$classroomUpdate = $this->db->prepare(DatabaseFiller::UPDATE_CLASSROOM_QUERY);
		foreach ($classrooms as $classroom) {
			$parameters = array(
				'department_id' => $this->departmentId,
				'code_insert' => $classroom->code,
				'code_update' => $classroom->code,
			);
			$classroomUpdate->execute($parameters);
		}

		$classroomSelect = $this->db->prepare(DatabaseFiller::SELECT_CLASSROOMS);
		$parameters = array(
			'department_id' => $this->departmentId,
		);
		$classroomSelect->execute($parameters);
		$classroomRows = $classroomSelect->fetchAll();
		foreach ($classroomRows as $row) {
			foreach ($classrooms as $index => $classroom) {
				if ($row['code'] == $classroom->code) {
					$classroom->id = $row['id'];
					$classrooms[$classroom->code] = $classroom;
					unset($classrooms[$index]);
				}
			}
		}
		print_r($classrooms);

		$subjectUpdate = $this->db->prepare(DatabaseFiller::UPDATE_SUBJECT_QUERY);
		foreach ($subjects as $subject) {
			$parameters = array(
				'department_id' => $this->departmentId,
				'abbreviation_insert' => $subject->abbreviation,
				'abbreviation_update' => $subject->abbreviation,
			);
			$subjectUpdate->execute($parameters);
		}

		$subjectsSelect = $this->db->prepare(DatabaseFiller::SELECT_SUBJECTS);
		$parameters = array(
			'department_id' => $this->departmentId,
		);
		$subjectsSelect->execute($parameters);
		$subjectRows = $subjectsSelect->fetchAll();
		foreach ($subjectRows as $row) {
			foreach ($subjects as $index => $subject) {
				if ($row['abbreviation'] == $subject->abbreviation) {
					$subject->id = $row['id'];
					$subjects[$subject->abbreviation] = $subject;
					unset($subjects[$index]);
				}
			}
		}
		print_r($subjects);

		$classUpdate = $this->db->prepare(DatabaseFiller::UPDATE_CLASS_QUERY);
		foreach ($classes as $class) {
			$parameters = array(
				'department_id' => $this->departmentId,
				'name_insert' => $class->name,
				'name_update' => $class->name,
			);
			$classUpdate->execute($parameters);
		}

		$classesSelect = $this->db->prepare(DatabaseFiller::SELECT_CLASSES);
		$parameters = array(
			'department_id' => $this->departmentId,
		);
		$classesSelect->execute($parameters);
		$classRows = $classesSelect->fetchAll();
		foreach ($classRows as $row) {
			foreach ($classes as $index => $class) {
				if ($row['name'] == $class->name) {
					$class->id = $row['id'];
					$classes[$class->name] = $class;
					unset($classes[$index]);
				}
			}
		}
		print_r($classes);

		$periodUpdate = $this->db->prepare(DatabaseFiller::UPDATE_PERIOD_QUERY);
		foreach ($periods as $period) {
			$parameters = array(
				'department_id' => $this->departmentId,
				'period_insert' => $period->getPeriod(),
				'period_update' => $period->getPeriod(),
				'start_insert' => $period->getStart(),
				'start_update' => $period->getStart(),
				'end_insert' => $period->getEnd(),
				'end_update' => $period->getEnd(),
				'title_insert' => $period->getTitle(),
				'title_update' => $period->getTitle(),
			);
			$periodUpdate->execute($parameters);
		}

		$periodsSelect = $this->db->prepare(DatabaseFiller::SELECT_PERIODS);
		$parameters = array(
			'department_id' => $this->departmentId,
		);
		$periodsSelect->execute($parameters);
		$periodRows = $periodsSelect->fetchAll();
		foreach ($periodRows as $row) {
			foreach ($periods as $index => $period) {
				//echo $row['period'] . ' - ' . $period->period . PHP_EOL;
				if ((string) Period::fromDatabase($row) == (string) $period) {
					$period->id = $row['id'];
					$periods[(string) $period] = $period;
					unset($periods[$index]);
				}
			}
		}

		$scheduleEntryUpdate = $this->db->prepare(DatabaseFiller::UPDATE_SCHEDULE_ENTRY);
		foreach ($scheduleEntries as $scheduleEntry) {

			$entryPeriods = array();

			$period = $periods[(string) $scheduleEntry->period];
			$entryPeriods[] = $period;
			while ($period = $period->getNext()) {
				$entryPeriods[] = $period;
			}

			print_r($entryPeriods);
	
			var_dump($scheduleEntry->length);
			for ($period = 0; $period < (int) $scheduleEntry->length; ++$period) {

				$parameters = array(
					'department_id' => $this->departmentId,
					'date_insert' => date('Y-m-d', $scheduleEntry->date),
				);

				$parameters['period_insert'] = $entryPeriods[$period]->id;
				echo 'Tijdvak: ' . $entryPeriods[$period]->getPeriod() . ' - ' . $entryPeriods[$period]->getStart() . PHP_EOL;

				$parameters['class_id_insert'] = $classes[$scheduleEntry->class]->id;
				echo 'Klas: ' . $classes[$scheduleEntry->class]->name . PHP_EOL;

				$parameters['subject_id_insert'] = $subjects[$scheduleEntry->subject]->id;
				$parameters['subject_id_update'] = $subjects[$scheduleEntry->subject]->id;
				echo 'Vak: ' . $subjects[$scheduleEntry->subject]->abbreviation . PHP_EOL;

				$parameters['classroom_id_insert'] = $classrooms[$scheduleEntry->classroom]->id;
				$parameters['classroom_id_update'] = $classrooms[$scheduleEntry->classroom]->id;
				echo 'Lokaal: ' . $classrooms[$scheduleEntry->classroom]->code . PHP_EOL;

				if ($scheduleEntry->teacher) {
					$parameters['teacher_id_insert'] = $teachers[$scheduleEntry->teacher]->id;
					$parameters['teacher_id_update'] = $teachers[$scheduleEntry->teacher]->id;
					echo 'Docent: ' . $teachers[$scheduleEntry->teacher]->abbreviation . PHP_EOL;
				} else {
					$parameters['teacher_id_insert'] = null;
					$parameters['teacher_id_update'] = null;
					echo 'Docent: Onbekend' . PHP_EOL;
				}

				//print_r($parameters);

				$scheduleEntryUpdate->execute($parameters);
			}
			echo '----------------------------------' . PHP_EOL;
		}

		$this->db->commit();
	}

}

<?php

$year = 2014;
$week = 27;
$departmentId = 1;

$db = new \PDO('mysql:host=mysql01.01d.eu;port=3306;dbname=mauris_live', 'mauris', '');

$data = json_decode(file_get_contents('lessen.json'));

$teachers = parseTeachers($data->ROOSTERS->DOCENTEN[0]);
$classes = parseClasses($data->ROOSTERS->KLASSEN[0]);
$subjects = parseSubjects($data->ROOSTERS->VAKKEN[0]);
$daggegevens = parseDays($data->ROOSTERS->DAGGEGEVENS[0]);
$lesuren = parseHours($data->ROOSTERS->LESUREN[0]);
$classrooms = parseClassRooms($data->ROOSTERS->LOKALEN[0]);
$rooster = $data->ROOSTERS->ROOSTER[0]->TYD;

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->beginTransaction();

$statementTeachers = $db->prepare("SELECT id, abbreviation, name FROM teachers");
$statementClasses = $db->prepare("SELECT id, name FROM classes");
$statementSubjects = $db->prepare("SELECT id, abbreviation FROM subjects");
$statementClassrooms = $db->prepare("SELECT id, code FROM classrooms");

$statementDeleteCurrentData = $db->prepare("DELETE FROM schedule_entries WHERE WEEK(date, 3) = :week AND YEAR(date) = :year AND :department_insert");
$statementDeleteCurrentData->execute(
	array(
		'week' => $week,
		'year' => $year,
		'department_insert' => $departmentId
	)
);

$teacherUpdate = $db->prepare(
	'INSERT INTO `teachers` (`abbreviation`, `name`, `department_id`) VALUES (:abbreviation_insert, :name_insert, :department_insert) ON DUPLICATE KEY UPDATE abbreviation = :abbreviation_update,
  name = :name_update;'
);
$classUpdate = $db->prepare(
	'INSERT INTO `classes` (`name`, `department_id`) VALUES (:name_insert, :department_insert) ON DUPLICATE KEY UPDATE name = :name_update;'
);
$subjectUpdate = $db->prepare(
	'INSERT INTO `subjects` (`abbreviation`, `department_id`) VALUES (:abbreviation_insert, :department_insert) ON DUPLICATE KEY UPDATE abbreviation = :abbreviation_update;'
);
$classroomUpdate = $db->prepare(
	'INSERT INTO `classrooms` (`code`, `department_id`) VALUES (:code_insert, :department_insert) ON DUPLICATE KEY UPDATE code = :code_update;'
);

foreach ($teachers as $index => $teacher) {
	$parameters = array(
		'abbreviation_insert' => $teacher['abbreviation'],
		'name_insert' => $teacher['name'],
		'department_insert' => $departmentId,
		'abbreviation_update' => $teacher['abbreviation'],
		'name_update' => $teacher['name'],
	);
	$teacherUpdate->execute($parameters);
}

foreach ($classes as $index => $class) {
	$parameters = array(
		'name_insert' => $class['name'],
		'department_insert' => $departmentId,
		'name_update' => $class['name'],
	);
	$classUpdate->execute($parameters);
}

foreach ($subjects as $index => $subject) {
	$parameters = array(
		'abbreviation_insert' => $subject['abbreviation'],
		'department_insert' => $departmentId,
		'abbreviation_update' => $subject['abbreviation'],
	);
	$subjectUpdate->execute($parameters);
}

foreach ($classrooms as $index => $classroom) {
	$parameters = array(
		'code_insert' => $classroom['code'],
		'department_insert' => $departmentId,
		'code_update' => $classroom['code'],
	);
	$classroomUpdate->execute($parameters);
}

$statementTeachers->execute();
$teacherRows = $statementTeachers->fetchAll();
foreach ($teacherRows as $row) {
	foreach ($teachers as &$teacher) {
		if ($row['abbreviation'] == $teacher['abbreviation']) {
			$teacher['id'] = $row['id'];
		}
	}
}

$statementClasses->execute();
$classRows = $statementClasses->fetchAll();
foreach ($classRows as $row) {
	foreach ($classes as &$class) {
		if ($row['name'] == $class['name']) {
			$class['id'] = $row['id'];
		}
	}
}

$statementSubjects->execute();
$subjectRows = $statementSubjects->fetchAll();
foreach ($subjectRows as $row) {
	foreach ($subjects as &$subject) {
		if ($row['abbreviation'] == $subject['abbreviation']) {
			$subject['id'] = $row['id'];
		}
	}
}

$statementClassrooms->execute();
$classroomRows = $statementClassrooms->fetchAll();
foreach ($classroomRows as $row) {
	foreach ($classrooms as &$classroom) {
		if ($row['code'] == $classroom['code']) {
			$classroom['id'] = $row['id'];
		}
	}
}

$scheduleEntryUpdate = $db->prepare(
	'INSERT INTO `schedule_entries` (`date`, `class_id`, `period`, `subject_id`, `teacher_id`, `classroom_id`, `department_id`) VALUES (STR_TO_DATE(CONCAT(:year, " ", :week, " ", :day), \'%x %v %w\'), :class_id_insert, :period_insert, :subject_id_insert, :teacher_id_insert, :classroom_id_insert, :department_insert) ON DUPLICATE KEY UPDATE subject_id = :subject_id_update, teacher_id = :teacher_id_update, classroom_id = :classroom_id_update;'
);

foreach ($rooster as $roosterding) {
	//var_dump($roosterding);
	foreach ($roosterding as $blabla => $anderding) {
		$teacherId = $anderding[2];
		$subjectId = $anderding[5];
		$classId = $anderding[3];
		$classRoomId = $anderding[4];
		$dayId = $anderding[0];
		$hourId = $anderding[1] - 1;
			echo '----------------------------------' . PHP_EOL;
			echo $blabla . PHP_EOL;
			echo implode(';', $anderding) . PHP_EOL;
			$parameters = array(
				'year'	=> (int) $year,
				'week'	=> (int) $week,
				'department_insert' => $departmentId
			);
			if ($dayId != 0) {
				$parameters['day'] = (int) (($dayId != 7) ? $dayId : 0);
				echo 'Dag: ' . $daggegevens[$dayId]['day'] . PHP_EOL;
			} else {
				echo 'Dag: Onbekend' . PHP_EOL;
			}
			if ($hourId != 0) {
				$parameters['period_insert'] = $hourId;
				echo 'Tijdvak: ' . $hourId . ' - ' . $lesuren[$hourId]['timeframe'] . PHP_EOL;
			} else {
				echo 'Tijdvak: Onbekend' . PHP_EOL;
			}
			if ($classId != 0) {
				$parameters['class_id_insert'] = $classes[$classId]['id'];
				echo 'Klas: ' . $classes[$classId]['name'] . PHP_EOL;
			} else {
				echo 'Klas: Onbekend' . PHP_EOL;
			}
			if ($subjectId != 0) {
				$parameters['subject_id_insert'] = $subjects[$subjectId]['id'];
				$parameters['subject_id_update'] = $subjects[$subjectId]['id'];
				echo 'Vak: ' . $subjects[$subjectId]['abbreviation'] . PHP_EOL;
			} else {
				echo 'Vak: Onbekend' . PHP_EOL;
			}
			if ($classRoomId != 0) {
				$parameters['classroom_id_insert'] = $classrooms[$classRoomId]['id'];
				$parameters['classroom_id_update'] = $classrooms[$classRoomId]['id'];
				echo 'Lokaal: ' . $classrooms[$classRoomId]['code'] . PHP_EOL;
			} else {
				echo 'Lokaal: Onbekend' . PHP_EOL;
			}
			if ($teacherId != 0) {
				$parameters['teacher_id_insert'] = $teachers[$teacherId]['id'];
				$parameters['teacher_id_update'] = $teachers[$teacherId]['id'];
				echo 'Docent: ' . $teachers[$teacherId]['name'] . PHP_EOL;
			} else {
				$parameters['teacher_id_insert'] = null;
				$parameters['teacher_id_update'] = null;
				echo 'Docent: Onbekend' . PHP_EOL;
			}
			if (count($parameters) == 12) {
				print_r($parameters);
				$scheduleEntryUpdate->execute($parameters);
			}
			echo '----------------------------------' . PHP_EOL;
	}
}

$db->commit();

function parseTeachers($teachers) {
	$parsed = array();
	foreach ($teachers as $teacher) {
		$parsedTeacher = array();
		$parsedTeacher['abbreviation'] = $teacher[0];
		$parsedTeacher['name'] = $teacher[1];
		$parsed[$teacher[2]] = $parsedTeacher;
	}
	return $parsed;
}

function parseClasses($units) {
	$parsed = array();
	foreach ($units as $unit) {
		$parsedUnit = array();
		$parsedUnit['name'] = $unit[0];
		$parsed[$unit[2]] = $parsedUnit;
	}

	return $parsed;
}

function parseClassRooms($units) {
	$parsed = array();
	foreach ($units as $unit) {
		$parsedUnit = array();
		$parsedUnit['code'] = $unit[0];
		$parsed[$unit[2]] = $parsedUnit;
	}

	return $parsed;
}

function parseHours($units) {
	$parsed = array();
	foreach ($units as $unit) {
		$parsedUnit = array();
		$parsedUnit['timeframe'] = $unit[1];
		$parsed[$unit[0]] = $parsedUnit;
	}

	return $parsed;
}

function parseDays($units) {
	$parsed = array();
	foreach ($units as $index => $unit) {
		$parsedUnit = array();
		$parsedUnit['day'] = $unit[0];
		$parsed[$index + 1] = $parsedUnit;
	}

	return $parsed;
}

function parseSubjects($subjects) {
	$parsed = array();
	foreach ($subjects as $subject) {
		$parsedSubject = array();
		$parsedSubject['abbreviation'] = $subject[0];
		$parsed[$subject[1]] = $parsedSubject;
	}
	return $parsed;
}

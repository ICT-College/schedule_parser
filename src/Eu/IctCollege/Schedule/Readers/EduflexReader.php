<?php

namespace Eu\IctCollege\Schedule\Readers;

use \Eu\IctCollege\Schedule\Models\ScheduleEntry;
use \Eu\IctCollege\Schedule\Models\ParsedSchedule;
use \Eu\IctCollege\Schedule\Models\Teacher;
use \Eu\IctCollege\Schedule\Models\Classroom;
use \Eu\IctCollege\Schedule\Models\Subject;
use \Eu\IctCollege\Schedule\Models\ClassEntry;
use \Eu\IctCollege\Schedule\Models\Period;

class EduflexReader {

	public function read($indexUrl) {
		/*$period = new Period();
		$period->setPeriod(1);
		$period->setStart('16:00');
		$period->setEnd('18:00');
		$period->setTitle('Blabla');
		print_r($period);exit();*/

		$classes = array();
		$periods = array();
		$allPeriods = array();
		$classScheduleLinks = array();
		$scheduleEntries = array();

		$bla = array();

		if (file_exists('entries.phps')) {
			list($classes, $bla, $periods, $allPeriods, $scheduleEntries) = unserialize(file_get_contents('entries.phps'));
		} else {
			$indexHtml = file_get_contents($indexUrl);

			$dom = new \domDocument;
			$dom->loadHTML($indexHtml);
			$dom->preserveWhiteSpace = false;
			$links = $dom->getElementsByTagName('a');
			foreach ($links as $link) {
				$url = $link->getAttribute('href');
				if (strstr($url, '..')) {
					continue;
				}

				$classes[] = $link->nodeValue;
				$classScheduleLinks[] = $url;
			}

			foreach ($classScheduleLinks as $classIndex => $classScheduleLink) {
				if ($classScheduleLink != '2P01947.htm') {
					//continue;
				}
				$classSchedule = file_get_contents($indexUrl . '/' . $classScheduleLink);
				$dom = new \domDocument;
				$dom->loadHTML($classSchedule);
				$dom->preserveWhiteSpace = false;
				$rows = $dom->getElementsByTagName('tr');
				$index = 0;
				foreach ($rows as $row) {
					$index++;
				
					$headers = $row->getElementsByTagName('th');

					// Used to get all the periods
					if ($index == 2) {
						$bla[$classes[$classIndex]] = array();
						$headerIndex = 0;
						$periodActualNr = 1;
						$periodExtraNr = 1;
						foreach ($headers as $header) {
							if ($headerIndex >= 2) {
								$bla[$classes[$classIndex]]['extra'][$periodExtraNr] = new Period($periodActualNr);
								$bla[$classes[$classIndex]]['extra'][$periodExtraNr]->setTitle($header->nodeValue);
								$periodExtraNr++;
								if ($header->nodeValue != 'P') {
									$bla[$classes[$classIndex]]['actual'][$periodActualNr] = new Period($periodActualNr);
									$bla[$classes[$classIndex]]['actual'][$periodActualNr]->setTitle($header->nodeValue);
									$periodActualNr++;
								}
								//$allPeriods[$headerIndex - 2][$index - 2] = $header->nodeValue;
							}
							++$headerIndex;
						}
					}
					if ($index == 3) {
						$headerIndex = 0;
						$periodActualNr = 1;
						$periodExtraNr = 1;
						foreach ($headers as $header) {
							if ($headerIndex >= 2) {
								//print_r($bla);
								$bla[$classes[$classIndex]]['extra'][$periodExtraNr]->setStart($header->nodeValue);
								if ($bla[$classes[$classIndex]]['extra'][$periodExtraNr]->getTitle() != 'P') {
									
									$bla[$classes[$classIndex]]['actual'][$periodActualNr]->setStart($header->nodeValue);
									$periodActualNr++;
								}
								++$periodExtraNr;
								//$allPeriods[$headerIndex - 2][$index - 2] = $header->nodeValue;
							}
							++$headerIndex;
						}
					}
					if (($index == 3) || ($index == 2)) {
						$headerIndex = 0;
						foreach ($headers as $header) {
							if ($headerIndex >= 2) {
								$allPeriods[$headerIndex - 2][$index - 2] = $header->nodeValue;
							}
							++$headerIndex;
						}
					}
					// Create a clean list of all the actual periods without pauses
					if ($index == 4) {
						foreach ($bla[$classes[$classIndex]]['extra'] as $number => $period) {
							if (isset($bla[$classes[$classIndex]]['extra'][$number + 1])) {
								$bla[$classes[$classIndex]]['actual'][$period->getPeriod()]
									->setEnd($bla[$classes[$classIndex]]['extra'][$number + 1]->getStart());
							} else {
								$periodLengths = array();
								$periodIndex = 0;
								foreach ($bla[$classes[$classIndex]]['actual'] as $periodAverage) {
									if ($periodIndex++ != count($bla[$classes[$classIndex]]['actual']) - 1) {
										$periodLengths[] = strtotime($periodAverage->getEnd()) - strtotime($periodAverage->getStart());
									}
								}
								//print_r($periodLengths);

								$average = round(array_sum($periodLengths) / count($periodLengths));
								$bla[$classes[$classIndex]]['actual'][$period->getPeriod()]->setEnd(date('H:i', strtotime($period->getStart()) + $average));
							}
						}
						foreach ($bla[$classes[$classIndex]]['actual'] as $period => $periodObject) {
							if (isset($bla[$classes[$classIndex]]['actual'][$period + 1])) {
								$bla[$classes[$classIndex]]['actual'][$period]->setNext($bla[$classes[$classIndex]]['actual'][$period + 1]);
							}
						}
						$periodNumber = 1;
						foreach ($allPeriods as $period) {
							if ($period[0] == 'P') {
								continue;
							}

							$periods[$periodNumber++] = $period;
						}
					}
		
					if ($index <= 3) {
						continue;
					}

					if (!$headers->length) {
						continue;
					}
				
					$date = strtotime($headers->item(1)->nodeValue);
					$period = 1;

					$cells = $row->getElementsByTagName('td');
					foreach ($cells as $cell) {
						if (!$cell->getAttribute('bgcolor')) {
							continue;
						}

						$data = $cell->getElementsByTagName('font');

						if ($data->length != 3) {
							$period++;//echo 'Optellen!' . PHP_EOL;
							continue;
						}

						$length = ($cell->getAttribute('colspan')) ? $cell->getAttribute('colspan') : 1;

						$pauses = 0;						
//$periodCounts = array_count_values(array_column(array_slice($allPeriods, 0, $period), 0));
					
						$periodWithPauses = $period + (($cell->getAttribute('colspan')) ? (int) $cell->getAttribute('colspan') : 0);

						foreach (array_slice($bla[$classes[$classIndex]]['extra'], 0, $periodWithPauses - 1) as $periodObject) {
							if ($periodObject->getTitle() == 'P') {
								$pauses++;
							}
						}
						var_dump(array_slice($bla[$classes[$classIndex]]['extra'], 0, $periodWithPauses));
						//$pauses = (isset($periodCounts['P'])) ? $periodCounts['P'] : 0;
						//echo 'Lessen: ' . PHP_EOL;						
						//print_r($pauses);
						//$periodCounts = array_count_values(array_column(array_slice($allPeriods, 0, $period), 0)));
					

						//if ($classes[$classIndex] == 'BC.C12MO.g') {
var_dump($period);
var_dump($length - $pauses);
print_r(array_slice($bla[$classes[$classIndex]]['extra'], 0, $period));
print_r(array_slice($allPeriods, 0, $period));
						echo 'Er zijn al ' . $pauses . ' pauzes geweest' . PHP_EOL;
						echo 'Periode raw: ' . $period . PHP_EOL;
						echo 'Periode zonder pauzes: ' . ($period - $pauses) . PHP_EOL;
						//}
					
						$scheduleEntry = new ScheduleEntry(
							$date,
							$bla[$classes[$classIndex]]['actual'][$period - $pauses],
							$length - $pauses,
							$classes[$classIndex],
							$subject = $data->item(2)->nodeValue,
							$classroom = $data->item(1)->nodeValue,
							$teacher = $data->item(0)->nodeValue
						);

						$scheduleEntries[] = $scheduleEntry;

						//print_r($scheduleEntry);

						$period += $length;
						//$period++;
					}
				}
			}
		}
//exit();

		file_put_contents('entries.phps', serialize(array($classes, $bla, $periods, $allPeriods, $scheduleEntries)));

		$teachers = array();
		$classrooms = array();
		$subjects = array();

		foreach ($scheduleEntries as $scheduleEntry) {
			$teachers[] = $scheduleEntry->teacher;
			$classrooms[] = $scheduleEntry->classroom;
			$subjects[] = $scheduleEntry->subject;
		}

		$teachers = array_values(array_unique($teachers));
		$classrooms = array_values(array_unique($classrooms));
		$subjects = array_values(array_unique($subjects));

		foreach ($teachers as &$teacher) {
			$teacher = new Teacher($teacher);
		}

		foreach ($classrooms as &$classroom) {
			$classroom = new Classroom($classroom);
		}

		foreach ($subjects as &$subject) {
			$subject = new Subject($subject);
		}

		foreach ($classes as &$class) {
			$class = new ClassEntry($class);
		}

		$periods = array();		
		foreach ($bla as $periodsByClass) {
			$periods = array_merge($periods, $periodsByClass['actual']);
		}
		$periods = array_values(array_unique($periods));

		print_r($periods);

		//print_r($allPeriods);

		/*foreach ($allPeriods as $period => &$periodData) {
			if ((count($allPeriods) - 1) != $period) {
				echo $period + 1 . '' . PHP_EOL;
				$end = $allPeriods[$period + 1][1];

				$periodData[2] = $end;	
			} else {
				$periodLengths = array();
				foreach ($allPeriods as $periodDataTime) {
					if ((!isset($periodDataTime[2])) || $periodDataTime[0] == 'P') {
						continue;
					}

					$periodLengths[] = strtotime($periodDataTime[2]) - strtotime($periodDataTime[1]);
				}
				print_r($periodLengths);

				$average = round(array_sum($periodLengths) / count($periodLengths));
				$periodData[2] = date('H:i', strtotime($periodDataTime[1]) + $average);
			}
		}

		print_r($allPeriods);

		exit();

		print_r($periods);*/

		/*foreach ($periods as $period => &$periodData) {
			$periodData = new Period($period, $periodData[1], $periodData[2], $periodData[0]);
		}*/

//		print_r($periods);exit()

		$parsed = new ParsedSchedule();
		$parsed->setTeachers($teachers);
		$parsed->setClassrooms($classrooms);
		$parsed->setSubjects($subjects);
		$parsed->setClasses($classes);
		$parsed->setPeriods($periods);
		$parsed->setScheduleEntries($scheduleEntries); 

		return $parsed;
	}

}

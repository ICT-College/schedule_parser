<?php

namespace Eu\IctCollege\Schedule\Models;

class Period {

	private $period;
	private $start;
	private $end;
	private $title;
	private $nextPeriod;

	const TIME_FORMAT = 'H:i:s';

	public function __construct($period = null, $start = null, $end = null, $title = null) {
		$this->setPeriod($period);
		$this->setStart($start);
		$this->setEnd($end);
		$this->setTitle($title);
	}

	public static function fromDatabase(array $row) {
		$period = new Period();

		$period->setPeriod($row['period']);
		$period->setStart($row['start']);
		$period->setEnd($row['end']);
		$period->setTitle($row['title']);
		
		return $period;
	}

	public function setPeriod($period) {
		$this->period = $period;
	}

	public function getPeriod() {
		return $this->period;
	}

	public function setStart($start) {
		$this->start = strtotime($start);
	}

	public function getStart() {
		return date(self::TIME_FORMAT, $this->start);
	}

	public function setEnd($end) {
		$this->end = strtotime($end);
	}

	public function getEnd() {
		return date(self::TIME_FORMAT, $this->end);
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getTitle() {
		return $this->title;
	}

	public function setNext(Period $nextPeriod) {
		$this->nextPeriod = $nextPeriod;
	}
	
	public function hasNext() {
		return (bool) $this->nextPeriod;
	}

	public function getNext() {
		return $this->nextPeriod;
	}

	public function __toString() {
		return implode(' ', array($this->getPeriod(), $this->getStart(), $this->getEnd(), $this->getTitle()));
	}

}

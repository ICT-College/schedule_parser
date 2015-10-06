<?php

namespace Eu\IctCollege\Schedule\Models;

class Subject {

	public $abbreviation;

	public function __construct($abbreviation) {
		$this->abbreviation = $abbreviation;
	}

}

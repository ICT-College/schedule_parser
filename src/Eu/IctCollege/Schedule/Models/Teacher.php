<?php

namespace Eu\IctCollege\Schedule\Models;

class Teacher {

	public $abbreviation;
	public $name;

	public function __construct($abbreviation, $name = null) {
		$this->abbreviation = $abbreviation;
		$this->name = $name;
	}

}

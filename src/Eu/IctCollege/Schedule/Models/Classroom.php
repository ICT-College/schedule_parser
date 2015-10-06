<?php

namespace Eu\IctCollege\Schedule\Models;

class Classroom {

	public $code;

	public function __construct($code) {
		$this->code = $code;
	}

}

<?php

$lines = file('27.rst');

$data = array();

$stack = new \SplStack();

foreach ($lines as $line) {
	$line = trim($line);
	$parts = explode(';', $line);

	if (substr($parts[0], 0, 5) == 'BEGIN') {
		//echo 'Begin ' . $parts[1] . PHP_EOL;
		$data[$parts[1]] = array();
		$stack->push($parts[1]);
	} elseif (substr($parts[0], 0, 3) == 'END') {
		//echo 'Eind ' . $parts[1] . PHP_EOL;
		$closedStackItem = $stack->pop();
		$data[$stack->top()][$closedStackItem][] = $data[$closedStackItem];
		unset($data[$closedStackItem]);
	} else {
		$data[$stack->top()][] = $parts;
	}
}

file_put_contents('lessen.json', json_encode($data));

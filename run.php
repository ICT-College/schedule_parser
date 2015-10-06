<?php

include 'src/SplClassLoader.php';

$classLoader = new SplClassLoader('Eu\\IctCollege\\Schedule', 'src');
$classLoader->register();

date_default_timezone_set('UTC');

use \Eu\IctCollege\Schedule\Readers\DatabaseReader;
use \Eu\IctCollege\Schedule\Readers\EduflexReader;
use \Eu\IctCollege\Schedule\Parsers\DatabaseParser;
use \Eu\IctCollege\Schedule\Parsers\RstParser;
use \Eu\IctCollege\Schedule\DatabaseFiller;
use \Eu\IctCollege\Schedule\Diff;

/*$parser = new RstParser();
$a = $parser->parse(file_get_contents('02.rst'), 2014, 2);
$b = $parser->parse(file_get_contents('Week 05.rst'), 2014, 2);

$diff = new Diff();
print_r($diff->diff($a, $b));*/

/*$reader = new DatabaseReader();
$output = $reader->read(2014, 2);

$parser = new DatabaseParser();*/

//print_r($parser->parse($output));


$url = "http://roosters.roc-teraa.nl/rooster_uitwisseling/ict-college/2P0/2015022320150417/";
//$url = 'http://roosters.roc-teraa.nl/rooster_uitwisseling/onderwijs-kinderopvang-college/2P0/2014012720140411/';

$reader = new EduflexReader();
$parsed = $reader->read($url);

//var_dump($parsed);exit();

$db = new \PDO('mysql:host=mysql01.01d.eu;dbname=mauris_live', 'mauris', '');

$filler = new DatabaseFiller($db, 1);
//$filler = new DatabaseFiller($db, 6);
$filler->fill($parsed);

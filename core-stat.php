<?php

error_reporting(E_ALL ^ E_NOTICE);

require('core-lib.php');
require('core-json.php');
require('core-table.php');

$baseDir = isset($argv[1]) ? $argv[1] : getcwd();
$baseDir .= substr($baseDir, -1) == '/' ? '' : '/';
$dataDir = isset($argv[2]) ? $argv[2] : getcwd();
$dataDir .= substr($dataDir, -1) == '/' ? '' : '/';
$modules = array('.', 'typo3/sysext/workspaces', 'typo3/sysext/extbase', 'typo3/sysext/fluid', 'typo3/sysext/dbal', 'typo3/sysext/version', 'typo3/sysext/linkvalidator');
$startdate = '2006-03-01';

$generator = new StatGenerator($dataDir, $baseDir, $startdate);

list($persons, $commitCounts) = $generator->generateData($modules);

$json = new JsonFormatter();
file_put_contents($dataDir . 'json.php', $json->generate($persons, $commitCounts, $generator->getMaxMonths()));
$table = new TableFormatter();
file_put_contents($dataDir . 'stat.html',$table->generate($persons, $commitCounts, $generator->getMaxMonths()));

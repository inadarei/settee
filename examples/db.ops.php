#!/usr/bin/env php

<?php

require (realpath(dirname(__FILE__) . '/../src/settee.php'));

$server = new SetteeServer('http://127.0.0.1:5984');


$dbs = array (
  1 => "settee_test_perf_01",
  2 => "settee_test_perf_02",
  3 => "settee_test_perf_03",
);

print ("creating databases: \n");

foreach ($dbs as $db) {
  $start = microtime(true);
  try {
    $ret = $server->create_db($db);
  } catch (Exception $e) {
    //-- re-throw. this is just for demo
    throw $e;
  }
  $elapsed = microtime(true) - $start;
  print("Time elapsed: $elapsed \n");
}

$ret = $server->list_dbs();
print_r($ret);
print ("\n");

print ("dropping databases: \n");

foreach ($dbs as $db) {
  $start = microtime(true);
  try {
    $ret = $server->drop_db($db);
  } catch (Exception $e) {
    //-- re-throw. this is just for demo
    throw $e;
  }
  $elapsed = microtime(true) - $start;
  print("Time elapsed: $elapsed \n");
}

$ret = $server->list_dbs();
print_r($ret);

#!/usr/bin/env php

<?php

require (realpath(dirname(__FILE__) . '/../src/settee.php'));

$server = new SetteeServer('http://127.0.0.1:5984');
$dname = 'irakli';
$db = $server->get_db('irakli');

try {
  $server->create_db($db);
} catch (Exception $e) {
  print_r("database irakli already exists! \n");
}

$doc = new StdClass();
$doc->firstName = "Irakli";
$doc->lastName = "Nadareishvili";
$doc->IQ = 200;
$doc->hobbies = array("skiing", "swimming");
$doc->pets = array ("whitey" => "labrador", "mikey" => "pug");

// Should work with json string as well:
//$doc = '{"firstName":"irakli","lastName":"Nadareishvili","IQ":200,"hobbies":["skiing","swimming"],"pets":{"whitey":"labrador","mikey":"pug"}}';

$doc = $db->save($doc);
print_r($doc);

$doc = $db->get($doc->_id);
print_r($doc);

$doc->firstName = "Ika";
$doc = $db->save($doc);
print_r($doc);

$db->delete($doc);



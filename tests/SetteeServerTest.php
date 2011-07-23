<?php

require_once (realpath(dirname(__FILE__) . '/../src/settee.php'));
require_once (dirname(__FILE__) . '/SetteeTestCase.class.php');

class SetteeServerTest extends SetteeTestCase {

  private $dbname;

  public function setUp() {
    parent::setUp();
    $this->dbname = "settee_tests_" . md5(microtime(true));
  }

  public function test_database_lifecycle_namebased() {
    $db = $this->server->get_db($this->dbname);
    $ret = $this->server->create_db($this->dbname);
    $this->assertTrue($ret->ok, "Database Creation Success Response [name-based]");

    $database_list = $this->server->list_dbs();
    $this->assertTrue(is_array($database_list) && in_array($this->dbname, $database_list),
                      "Verifying Database in the List on the Server [name-based]");

    $ret = $this->server->drop_db($this->dbname);
    $this->assertTrue($ret->ok, "Database Deletion Success Response [name-based]");
  }

  public function test_database_lifecycle_objectbased() {
    $db = $this->server->get_db($this->dbname);
    $ret = $this->server->create_db($db);
    $this->assertTrue($ret->ok, "Database Creation Success Response [object-based]");

    $database_list = $this->server->list_dbs();
    $this->assertTrue(is_array($database_list) && in_array($this->dbname, $database_list),
                      "Verifying Database in the List on the Server [object-based]");

    $ret = $this->server->drop_db($db);
    $this->assertTrue($ret->ok, "Database Deletion Success Response [object-based]");
  }

}


<?php

/**
 * Abstract parent for Settee test classes.
 */
abstract class SetteeTestCase extends PHPUnit_Framework_TestCase {

  protected $server;
  protected $db_url;
  protected $db_user;
  protected $db_pass;

  public function setUp() {
    $this->db_url  = isset($GLOBALS['db_url'])  ? $GLOBALS['db_url']  : 'http://127.0.0.1:5984';
    $this->db_user = isset($GLOBALS['db_user']) ? $GLOBALS['db_user'] : 'admin';
    $this->db_pass = isset($GLOBALS['db_pass']) ? $GLOBALS['db_pass'] : 'admin';
    $this->server = new SetteeServer($this->db_url);
  }

}
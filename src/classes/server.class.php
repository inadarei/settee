<?php

/**
* CouchDB Server Manager
*/
class SetteeServer {

  /**
  * Base URL of the CouchDB REST API
  */
  private $conn_url;
  
  /**
  * HTTP REST Client instance
  */
  protected $rest_client;

  
  /**
  * Class constructor
  */
  function __construct($conn_url) {
    $this->conn_url = rtrim($conn_url, ' /');
    $this->rest_client = SetteeRestClient::get_instance($this->conn_url);
  }
  
  /**
  * Create database
  *
  * @param $db
  *     Either a database object or a String name of the database.
  *
  * @return
  *     json string from the server.
  *
  *  @throws SetteeCreateDatabaseException
  */
  function create_db($db) {
    if ($db instanceof SetteeDatabase) {
      $db = $db->get_name();
    }
    $ret = $this->rest_client->http_put($db);
    if (!empty($ret['decoded']["error"])) {
      throw new SetteeDatabaseException("Could not create database: " . $ret["json"]);
    }
    return $ret['decoded'];
  }
  
  /**
  * Drop database
  *
  * @param $db
  *     Either a database object or a String name of the database.
  *
  * @return
  *     json string from the server.
  *
  *  @throws SetteeDropDatabaseException
  */
  function drop_db($db) {
    if ($db instanceof SetteeDatabase) {
      $db = $db->get_name();
    }
    $ret =  $this->rest_client->http_delete($db);
    if (!empty($ret['decoded']["error"])) {
      throw new SetteeDatabaseException("Could not create database: " . $ret["json"]);
    }
    return $ret['decoded'];
  }
  
  /**
  * Instantiate a database object
  *
  * @param $dbname
  *    name of the newly created database
  *
  * @return SetteeDatabase
  *     new SetteeDatabase instance.
  */
  function get_db($dbname) {
    return new SetteeDatabase($this->conn_url, $dbname);
  }


  /**
  * Return an array containing all databases
  *
  * @return Array
  *    an array of database names in the CouchDB instance
  */
  function list_dbs() {
    $ret = $this->rest_client->http_get('_all_dbs');
    if (!empty($ret['decoded']["error"])) {
      throw new SetteeDatabaseException("Could not get list of databases: " . $ret["json"]);
    }
    return $ret['decoded'];
  }

}

class SetteeServerErrorException extends Exception {}
class SetteeDatabaseException extends Exception {}

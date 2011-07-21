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
    $this->conn_url = $conn_url;
    $this->rest_client = SetteeRestClient::get_instance($this->conn_url);
  }
  
  /**
  * Create database
  * @param $db
  *     String name of the database.
  *
  * @return
  *     json string from the server.
  *
  *  @throws SetteeCreateDatabaseException
  */
  function create_db($dbname) {
    $ret = $this->rest_client->http_put($dbname);
    $ret_decoded = json_decode($ret, true);
    if (!empty($ret_decoded["error"])) {
      throw new SetteeCreateDatabaseException("Could not create database: " . $ret);
    }
    return $ret_decoded;
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
    $ret_decoded = json_decode($ret, true);
    if (!empty($ret_decoded["error"])) {
      throw new SetteeDropDatabaseException("Could not drop database: " . $ret);
    }
    return $ret_decoded;
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
    $resp = $this->rest_client->http_get('_all_dbs');   
    $list = json_decode($resp, true);
    return $list;
  }

}

class SetteeDropDatabaseException extends Exception {}
class SetteeCreateDatabaseException extends Exception {}
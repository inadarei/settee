<?php

/**
* CouchDB Server Manager
*/
class settee_server {

  /**
  * Base URL of the CouchDB REST API
  */
  protected $conn_url;
  
  /**
  * HTTP REST Client instance
  */
  protected $rest_client;

  
  /**
  * Class constructor
  */
  function __construct($conn_url) {
    $this->conn_url = $conn_url;
    $this->rest_client = new settee_restclient($this->conn_url);
  }
  
  /**
  * Create database
  */
  function create_database($dbname) {
  }
  
  /**
  * Drop database
  */
  function drop_database($dbname) {
    return $this->rest_client->delete($dbname);  
  }
  
  /**
  * Return a database object
  */
  function get_database($dbname) {
    return new settee_database($conn_url, $dbname);
  }


}

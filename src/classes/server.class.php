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
  function create_db($dbname) {
    return $this->rest_client->put($dbname);  
  }
  
  /**
  * Drop database
  */
  function drop_db($dbname) {
    return $this->rest_client->delete($dbname);  
  }
  
  /**
  * Return a database object
  */
  function get_db($dbname) {
    return new settee_database($conn_url, $dbname);
  }


  /**
  * Return a database object
  */
  function list_dbs() {
    $resp = $this->rest_client->get('_all_dbs');   
    $resp = json_decode($resp, true);
    print_r($resp);
  }


}

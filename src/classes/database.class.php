<?php

/**
* Databaase class.
*/
class  settee_database {

  /**
  * Base URL of the CouchDB REST API
  */
  protected $conn_url;
  
  /**
  * HTTP REST Client instance
  */
  protected $rest_client;
  
  /**
  * Name of the database
  */
  protected $dbname;
  
  /**
  * Default constructor
  */ 
  function __construct($conn_url, $dbname) {
    $this->conn_url = $conn_url;
    $this->dbname = $dbname;
    $this->rest_client = new settee_restclient($this->conn_url);
  }
  
  /**
  * Save a document
  */
  function save() {
  }
  
  /**
  * Get a document
  */
  function get() {
  }

  /**
  * Delete a document
  */  
  function delete() {
  }
   
}
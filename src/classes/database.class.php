<?php

/**
* Databaase class.
*/
class SetteeDatabase {

  /**
  * Base URL of the CouchDB REST API
  */
  private $conn_url;
  
  /**
  * HTTP REST Client instance
  */
  protected $rest_client;
  
  /**
  * Name of the database
  */
  private $dbname;
  
  /**
  * Default constructor
  */ 
  function __construct($conn_url, $dbname) {
    $this->conn_url = $conn_url;
    $this->dbname = $dbname;
    $this->rest_client = SetteeRestClient::get_instance($this->conn_url);
  }


  /**
  * Get UUID from CouchDB
  *
  * @return
  *     CouchDB-generated UUID string
  *
  */
  function gen_uuid() {
    $ret = $this->rest_client->http_get('_uuids');
    $ret_decoded = json_decode($ret, true);
    if (!empty($ret_decoded["error"])) {
      throw new SetteeServerErrorException("CouchDB Error: " . $ret);
    }
    return $ret_decoded['uuids'][0]; // should never be empty at this point, so no checking
  }

  /**
  * Create database
  * @param $document
  *     PHP object or a JSON String representing the document to be saved. PHP Objects are JSON-encoded automatically.
  *
  * @return
  *     json string from the server.
  *
  *  @throws SetteeCreateDatabaseException
  */
  function create($document, $uuid = null) {
    if (is_object($document) || is_array($document)) {
      $document = json_encode($document, JSON_NUMERIC_CHECK);
    }
    if (empty($uuid)) {
      $uuid = $this->gen_uuid();
    }

    $full_uri = $this->dbname . "/$uuid";

    $ret = $this->rest_client->http_put($full_uri, $document);
    $ret_decoded = json_decode($ret, true);
    if (!empty($ret_decoded["error"])) {
      throw new SetteeCreateDocumentException("Could not create document: " . $ret);
    }
    return $ret_decoded['rev'];
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
  
  /** Getter for database name */
  function get_name() {
    return $this->dbname;
  }
   
}

class SetteeCreateDocumentException extends Exception {}
class SetteeSaveDocumentException extends Exception {}
class SetteeDeleteDocumentException extends Exception {}
class SetteeLoadDocumentException extends Exception {}
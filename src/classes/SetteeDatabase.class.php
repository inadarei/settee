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
    return $ret['decoded']['uuids'][0]; // should never be empty at this point, so no checking
  }

  /**
  * Create database
  *
  * @param $document
  *     PHP object or a JSON String representing the document to be saved. PHP Objects are JSON-encoded automatically.
  *
  * @param $id
  *    you can supply your own UUID, if you do not want CouchDB to generate one for you.
  *
  * @return
  *     document object with the database id (uuid) and revision attached;
  *
  *  @throws SetteeCreateDatabaseException
  */
  function create($document, $id = null) {
    if (is_object($document) || is_array($document)) {
      $document_json = json_encode($document, JSON_NUMERIC_CHECK);
    }
    else {
      $document_json = $document;
    }

    if (empty($id)) {
      $id = $this->gen_uuid();
    }

    $full_uri = $this->dbname . "/$id";

    $ret = $this->rest_client->http_put($full_uri, $document_json);
    if (!is_object($document)) {
      $document = json_decode($document);
    }
    $document->_id = $ret['decoded']['id'];
    $document->_rev = $ret['decoded']['rev'];
    return $document;
  }

  /**
  * Save a document
  */
  function save() {
  }
  
  /**
   * @throws SetteeWrongInputException
   * @param  $id
   *    Unique ID (usually: UUID) of the document to be retrieved.
   * @return
   *    database document in PHP object format.
   */
  function get($id) {
    if (empty($id)) {
      throw new SetteeWrongInputException("Error: Can't retrieve a document without a uuid.");
    }

    $full_uri = $this->dbname . "/$id";

    $ret = $this->rest_client->http_get($full_uri);
    return $ret['decoded'];
  }

  /**
  * Delete a document
  *
  * @param $document
  *    a PHP object that has _id and _rev fields.
  *
  * @return void 
  */  
  function delete($document) {
    $full_uri = $this->dbname . "/" . $document->_id . "?rev=" . $document->_rev;
    print_r($full_uri);
    $this->rest_client->http_delete($full_uri);
  }
  
  /** Getter for a database name */
  function get_name() {
    return $this->dbname;
  }
   
}
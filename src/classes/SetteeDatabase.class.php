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
    return $ret['decoded']->uuids[0]; // should never be empty at this point, so no checking
  }

  /**
  * Create or update a document database
  *
  * @param $document
  *     PHP object or a JSON String representing the document to be saved. PHP Objects are JSON-encoded automatically.
  *
  * <p>If $document has a an "_id" property set, it will be used as document's unique id (even for "create" operation).
  * If "_id" is missing, CouchDB will be used to generate a UUID.
  *
  * <p>If $document has a "_rev" property (revision), document will be updated, rather than creating a new document.
  * You have to provide "_rev" if you want to update an existing document, otherwise operation will be assumed to be
  * one of creation and you will get a duplicate document exception from CouchDB. Also, you may not provide "_rev" but
  * not provide "_id" since that is an invalid input.
  *
  * @return
  *     document object with the database id (uuid) and revision attached;
  *
  *  @throws SetteeCreateDatabaseException
  */
  function save($document) {
    if (is_object($document) || is_array($document)) {
      $document_json = json_encode($document, JSON_NUMERIC_CHECK);
    }
    else {
      $document_json = $document;
      $document = json_decode($document);
    }

    if (empty($document->_id) && empty($document->_rev)) {
      $id = $this->gen_uuid();
    }
    elseif (empty($document->_id) && !empty($document->_rev)) {
      throw new SetteeWrongInputException("Error: You can not save a document with a revision provided, but missing id");
    }
    else {
      $id = $document->_id;
    }

    $full_uri = $this->dbname . "/" . urlencode($id);

    $ret = $this->rest_client->http_put($full_uri, $document_json);
    if (!is_object($document)) {
      $document = json_decode($document);
    }
    $document->_id = $ret['decoded']->id;
    $document->_rev = $ret['decoded']->rev;

    return $document;
  }

  /**
   * @param  $doc
   * @param  $name
   * @param  $content
   *    Content of the attachment in a string-buffer format. This function will automatically base64-encode content for
   *    you, so you don't have to do it.
   * @param  $mime_type
   *    Optional. Will be auto-detected if not provided
   * @return void
   */
  public function add_attachment($doc, $name, $content, $mime_type = null) {
    if (empty($doc->_attachments) || !is_object($doc->_attachments)) {
      $doc->_attachments = new stdClass();
    }

    if (empty($mime_type)) {
      $mime_type = $this->rest_client->content_mime_type($content);
    }

    $doc->_attachments->$name = new stdClass();
    $doc->_attachments->$name->content_type = $mime_type;
    $doc->_attachments->$name->data = base64_encode($content);
  }

  /**
   * @param  $doc
   * @param  $name
   * @param  $file
   *    Full path to a file (e.g. as returned by PHP's realpath function).
   * @param  $mime_type
   *    Optional. Will be auto-detected if not provided
   * @return void
   */
  public function add_attachment_file($doc, $name, $file, $mime_type = null) {
    $content = file_get_contents($file);
    $this->add_attachment($doc, $name, $content, $mime_type);
  }

  /**
   *
   * Retrieve a document from CouchDB
   *
   * @throws SetteeWrongInputException
   * 
   * @param  $id
   *    Unique ID (usually: UUID) of the document to be retrieved.
   * @return
   *    database document in PHP object format.
   */
  function get($id) {
    if (empty($id)) {
      throw new SetteeWrongInputException("Error: Can't retrieve a document without a uuid.");
    }

    $id = urldecode($id);
    $full_uri = $this->dbname . "/" . urlencode($id);

    $ret = $this->rest_client->http_get($full_uri);
    return $ret['decoded'];
  }

    /**
   *
   * Get the latest revision of a document with document id: $id in CouchDB.
   *
   * @throws SetteeWrongInputException
   *
   * @param  $id
   *    Unique ID (usually: UUID) of the document to be retrieved.
   * @return
   *    database document in PHP object format.
   */
  function get_rev($id) {
    if (empty($id)) {
      throw new SetteeWrongInputException("Error: Can't query a document without a uuid.");
    }

    $id = urldecode($id);
    $full_uri = $this->dbname . "/" . urlencode($id);

    $ret = $this->rest_client->http_head($full_uri);
    return $ret['decoded'];
  }
  
  /**
  * Delete a document
  *
  * @param $document
  *    a PHP object or JSON representation of the document that has _id and _rev fields.
  *
  * @return void 
  */  
  function delete($document) {
    if (!is_object($document)) {
      $document = json_decode($document);
    }

    $full_uri = $this->dbname . "/" . urlencode($document->_id) . "?rev=" . $document->_rev;
    $this->rest_client->http_delete($full_uri);
  }
  
  /** Getter for a database name */
  function get_name() {
    return $this->dbname;
  }

}
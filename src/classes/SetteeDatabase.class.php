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
  *     PHP object, a PHP associative array, or a JSON String representing the document to be saved. PHP Objects and arrays are JSON-encoded automatically.
  *
  * <p>If $document has a an "_id" property set, it will be used as document's unique id (even for "create" operation).
  * If "_id" is missing, CouchDB will be used to generate a UUID.
  *
  * <p>If $document has a "_rev" property (revision), document will be updated, rather than creating a new document.
  * You have to provide "_rev" if you want to update an existing document, otherwise operation will be assumed to be
  * one of creation and you will get a duplicate document exception from CouchDB. Also, you may not provide "_rev" but
  * not provide "_id" since that is an invalid input.
  *
  * @param $allowRevAutoDetection
  *   Default: false. When true and _rev is missing from the document, save() function will auto-detect latest revision
  * for a document and use it. This option is "false" by default because it involves an extra http HEAD request and
  * therefore can make save() operation slightly slower if such auto-detection is not required.
  *
  * @return
  *     document object with the database id (uuid) and revision attached;
  *
  *  @throws SetteeCreateDatabaseException
  */
  function save($document, $allowRevAutoDetection = false) {
    if (is_string($document)) {
      $document = json_decode($document);
    }

    // Allow passing of $document as an array (for syntactic simplicity and also because in JSON world it does not matter) 
    if(is_array($document)) {
      $document = (object) $document;
    }

    if (empty($document->_id) && empty($document->_rev)) {
      $id = $this->gen_uuid();
    }
    elseif (empty($document->_id) && !empty($document->_rev)) {
      throw new SetteeWrongInputException("Error: You can not save a document with a revision provided, but missing id");
    }
    else {
      $id = $document->_id;

      if ($allowRevAutoDetection) {
        try {
          $rev = $this->get_rev($id);
        } catch (SetteeRestClientException $e) {
          // auto-detection may fail legitimately, if a document has never been saved before (new doc), so skipping error
        }
        if (!empty($rev)) {
          $document->_rev = $rev;
        }
      }
    }
    
    $full_uri = $this->dbname . "/" . $this->safe_urlencode($id);
    if(version_compare(PHP_VERSION, '5.3.3') >= 0) {
        $document_json = json_encode($document, JSON_NUMERIC_CHECK);
    } else {
        $document_json = json_encode($document);
    }
    $ret = $this->rest_client->http_put($full_uri, $document_json);

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

    $full_uri = $this->dbname . "/" . $this->safe_urlencode($id);

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

    $full_uri = $this->dbname . "/" . $this->safe_urlencode($id);
    $headers = $this->rest_client->http_head($full_uri);
	if (empty($headers['Etag'])) {
	  throw new SetteeRestClientException("Error: could not retrieve revision. Server unexpectedly returned empty Etag");
	}
    $etag = str_replace('"', '', $headers['Etag']);
    return $etag;
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

    $full_uri = $this->dbname . "/" . $this->safe_urlencode($document->_id) . "?rev=" . $document->_rev;
    $this->rest_client->http_delete($full_uri);
  }

  
  /*-----------------  View-related functions --------------*/

  /**
   * Create a new view or update an existing one.
   *
   * @param  $design_doc
   * @param  $view_name
   * @param  $map_src
   *    Source code of the map function in Javascript
   * @param  $reduce_src
   *    Source code of the reduce function  in Javascript (optional)
   * @return void
   */
  function save_view($design_doc, $view_name, $map_src, $reduce_src = null) {
    $obj = new stdClass();
    $obj->_id = "_design/" . urlencode($design_doc);
    $view_name = urlencode($view_name);
    $obj->views->$view_name->map = $map_src;
    if (!empty($reduce_src)) {
      $obj->views->$view_name->reduce = $reduce_src;
    }

    // allow safe updates (even if slightly slower due to extra: rev-detection check).
    return $this->save($obj, true);
  }

  /**
   * Create a new view or update an existing one.
   *
   * @param  $design_doc
   * @param  $view_name
   * @param  $key
   *    key parameter to a view. Can be a single value or an array (for a range). If passed an array, function assumes
   *    that first element is startkey, second: endkey.
   * @param  $descending
   *    return results in descending order. Please don't forget that if you are using a startkey/endkey, when you change
   *  order you also need to swap startkey and endkey values!
   * 
   * @return void
   */
  function get_view($design_doc, $view_name, $key = null, $descending = false) {
    $id = "_design/" . urlencode($design_doc);
    $view_name = urlencode($view_name);
    $id .= "/_view/$view_name";
    $data = array();
    if (!empty($key)) {
      if (!is_array($key)) {
        $data = "key=" . $key;
      }
      else {
        list($startkey, $endkey) = $key;
        $data = "startkey=" . $startkey . '&' . "endkey=" . $endkey;
      }

      if ($descending) {
        $data .= "&descending=true";
      }
    }



    if (empty($id)) {
      throw new SetteeWrongInputException("Error: Can't retrieve a document without a uuid.");
    }

    $full_uri = $this->dbname . "/" . $this->safe_urlencode($id);

    $ret = $this->rest_client->http_get($full_uri, $data);
    return $ret['decoded'];
    
  }

  /**
   * @param  $id
   * @return
   *    return a properly url-encoded id.
   */
  private function safe_urlencode($id) {
    //-- System views like _design can have "/" in their URLs.
    $id = rawurlencode($id);
    if (substr($id, 0, 1) == '_') {
      $id = str_replace('%2F', '/', $id);
    }
    return $id;
  }
  
  /** Getter for a database name */
  function get_name() {
    return $this->dbname;
  }
  
  /**
   * Return SetteeRestClient
   *
   * @return SetteeRestClient
   */
  public function get_rest_client()
  {
      return $this->rest_client;
  }

  /**
   * Extended view quering. This function can:
   * * pass extra parameter to view like include_docs(@param: $view_params),
   * * fetch collection from view based on keys array,
   * * fetch collection from _all_docs based on keys array
   * 
   * If $start_key is passed as array of parameters POST request
   * will occur with body: array("keys" => YOUR_ARRAY). In addition
   * if $design_doc is FALSE query to _all_docs will be done.
   *
   * @param mixed $design_doc
   * @param mixed $view_name
   * @param mixed $start_key
   * @param mixed $end_key
   * @param array $view_params
   * @return object
   */
  public function get_view_extended($design_doc, $view_name = FALSE, $start_key = FALSE, $end_key = FALSE
          , array $view_params = array(), $suppressPost = FALSE)
  {
    // encode view parameters
    foreach($view_params as $key => &$val) {
        $val = json_encode($val);
    }
    // prepare keys array as param
    if(is_array($start_key) && !$suppressPost) {
        if(!array_key_exists('keys', $start_key)) {
            $start_key = array('keys' => $start_key);
        }
        
        if(version_compare(PHP_VERSION, '5.3.3') >= 0) {
            $data_json = json_encode($start_key, JSON_NUMERIC_CHECK);
        } else {
            $data_json = json_encode($start_key);
        }
        
        $query_string = !empty($view_params) ? '?' . urldecode(http_build_query($view_params)) : '';
        if($design_doc == FALSE) {
            // case when we requesting _all_docs
            $ret = $this->rest_client->http_request('POST',
                $this->dbname . '/_all_docs' . $query_string, $data_json);
        } else {
            $ret = $this->rest_client->http_request('POST', 
                    $this->dbname . '/_design/' . $design_doc .'/_view/' . $view_name . $query_string, 
                    $data_json);
        }
    } else {
        if($start_key && !$end_key) {
            $view_params['key'] = json_encode($start_key);
        } else if($start_key && $end_key) {
            $view_params['startkey'] = json_encode($start_key);
            $view_params['endkey'] = json_encode($end_key);
        }
        
        $query_string = !empty($view_params) ? '?' . urldecode(http_build_query($view_params)) : '';
        $full_uri = $this->dbname . "/" .  $this->safe_urlencode('_design/' . $design_doc . '/_view/' . $view_name) . $query_string;
        $ret = $this->rest_client->http_request('GET', $full_uri);
    }

    return $ret['decoded'];
  }
}
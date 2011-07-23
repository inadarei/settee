<?php

/**
* HTTP REST Client for CouchDB API
*/
class SetteeRestClient {
  
  /**
  * HTTP Timeout in Milliseconds
  */
  const HTTP_TIMEOUT = 2000;
  
  private $base_url;
  private $curl;
  
  private static $curl_workers = array();

  /**
  * Singleton factory method   
  */
  function get_instance($base_url) {

    if (empty($this->curl_workers[$base_url])) {
      $this->curl_workers[$base_url] = new SetteeRestClient($base_url);
    }
    
    return $this->curl_workers[$base_url];
  }
  
  /**
  * Class constructor
  */
  private function __construct($base_url) {
    $this->base_url = $base_url;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERAGENT, "Settee CouchDB Client/1.0");
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT_MS, self::HTTP_TIMEOUT);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, false); // Connection-pool for CURL

    $this->curl = $curl;
    
  }

  /**
  * Class destructor cleans up any resources
  */
  function __destruct() {
     curl_close($this->curl);
  }
  
  /**
  * HTTP GET
  */
  function http_get($uri, $data = array()) {
    $data = (is_array($data)) ? http_build_query($data) : urlencode(trim($data));
    if (!empty($data)) {
      $uri .= "?$data";
    }

    return $this->http_request('GET', $uri);
  }
  
  /**
  * HTTP PUT
  */
  function http_put($uri, $data = array()) {
    return $this->http_request('PUT', $uri, $data);
  }

  /**
  * HTTP DELETE
  */
  function http_delete($uri, $data = array()) {
    return $this->http_request('DELETE', $uri, $data);
  }

  /**
   * Generic implementation of a HTTP Request.
   *
   * @param $http_method
   * @param  $uri
   * @param array $data
   * @return
   *  an array containing json and decoded versions of the response.
   */
  private function http_request($http_method, $uri, $data = array()) {
    $data = (is_array($data)) ? http_build_query($data) : $data;

    if (!empty($data)) {
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($data)));
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
    }

    curl_setopt($this->curl, CURLOPT_URL, $this->get_full_url($uri));
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $http_method);

    $response = curl_exec($this->curl);
    $response_decoded = $this->decode_response($response);
    $response = array('json' => $response, 'decoded'=>$response_decoded);

    $this->check_status($response);

    return $response;
  }
  
  /**
   * Check http status for safe return codes
   *
   * @throws SetteeRestClientException
   */
  private function check_status($response) {
    $resp_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

    if ($resp_code < 199 || $resp_code > 399 || !empty($response['decoded']->error)) {
      $msg = "CouchDB returned: \"HTTP 1.1. $resp_code\". ERROR: " . $response['json'];
      throw new SetteeRestClientException($msg);
    }
  }

  /**
   *
   * @param $json
   *    json-encoded response from CouchDB
   * 
   * @return
   *    decoded PHP object
   */
  private function decode_response($json) {
    return json_decode($json);
  }

  /**
  * Get full URL from a partial one
  */
  private function get_full_url($uri) {
    // We do not want "/", "?", "&" and "=" separators to be encoded!!!
    $uri = str_replace(array('%2F', '%3F', '%3D', '%26'), array('/', '?', '=', '&'), urlencode($uri));
    return $this->base_url . '/' . $uri;
  }
}

class SetteeRestClientException extends Exception {}
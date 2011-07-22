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

  private $http_response_headers;
  
  /**
  * Singleton factory method   
  */
  function get_instance($base_url) {

    if (empty($curl_workers[$base_url])) {
      $curl_workers[$base_url] = new SetteeRestClient($base_url);
    }
    
    return $curl_workers[$base_url];
  }
  
  /**
  * Class constructor
  */
  private function __construct($base_url) {
    $this->base_url = $base_url;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERAGENT, "Settee CouchDB Client");
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
    $url = $this->get_full_url($uri);
    if (!empty($data)) {
      $url .= "?$data";
    }
    curl_setopt($this->curl, CURLOPT_URL, $url);
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "GET");
    //curl_setopt($this->curl, CURLOPT_HTTPGET, true);

    $this->http_response_headers = array();
    $response = curl_exec($this->curl);

    $err = curl_error($this->curl);
    if (!empty($err)) {
      throw SetteeServerException("$err");
    }
    try {
      $this->check_status();
    } catch (SetteeRestClientException $e) {
      throw $e;
    }
    return $response;
  }  
  
  /**
  * HTTP PUT
  */
  function http_put($uri, $data = array()) {
    $data = (is_array($data)) ? http_build_query($data) : $data;

    if (empty($data)) {
      curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array($this, '_return_header_check'));
    }
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($data)));
    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

    curl_setopt($this->curl, CURLOPT_URL, $this->get_full_url($uri));
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "PUT");
    $this->http_response_headers = array();
    $response = curl_exec($this->curl);
    try {
      $this->check_status();
    } catch (SetteeRestClientException $e) {
      throw $e;
    }
    return $response;
  }

  /**
  * HTTP DELETE
  */
  function http_delete($uri, $data = array()) {

    curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array($this, '_return_header_check'));
    curl_setopt($this->curl, CURLOPT_URL, $this->get_full_url($uri));
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
    $this->http_response_headers = array();
    $response = curl_exec($this->curl);
    try {
      $this->check_status();
    } catch (SetteeRestClientException $e) {
      throw $e;
    }
    return $response;
  }

  /**
   * Check http status for safe return codes
   *
   * @throws SetteeRestClientException
   */
  private function check_status() {
    $resp_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    if (empty($resp_code)) { return; }
    if ($resp_code < 199 || $resp_code > 399) {
      if (is_array($this->http_response_headers) && !empty($this->http_response_headers[0])) {
        $header = $this->http_response_headers[0];
        $msg = "Error connecting to CouchDB. Server returned: \"$header\". Aborting. \n";
      } else {
        $msg = "Error connecting to CouchDB. Server returned: \"HTTP 1.1. $resp_code\". Aborting. \n";
      }

      throw new SetteeRestClientException($msg);
    }
  }

  /**
   * @param  $ch
   * @param  $header
   * @return void
   */
  private function _return_header_check($ch, $header) {
     $resp_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
     if (empty($resp_code)) { return; }
     if ($resp_code < 199 || $resp_code > 399) {
       $header = str_replace(array("\n", "\r"), array('', ''), $header);
       $this->http_response_headers[] = $header;
     }
  }


  /**
  * Get full URL from partial one
  */
  private function get_full_url($uri) {
    // We do not want "/" separators to be encoded!!!
    $uri = str_replace('%2F', '/', rawurlencode($uri));
    return $this->base_url . '/' . $uri;
  }
}

class SetteeRestClientException extends Exception {}
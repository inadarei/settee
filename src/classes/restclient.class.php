<?php

/**
* HTTP REST Client for CouchDB API
*/
class settee_restclient {
  
  /**
  * HTTP Timeout in Milliseconds
  */
  const HTTP_TIMEOUT = 2000;
  
  protected $base_url;
  protected $curl;
  
  private static $curl_workers = array();
  
  /**
  * Singleton factory method   
  */
  function get_instance($base_url) {
    if (empty($curl_workers[$base_url])) {
      $curl_workers[$base_url] = new settee_restclient($base_url);
    }
    
    return $curl_workers[$base_url];
  }
  
  /**
  * Class constructor
  */
  private function __construct($base_url) {
    $this->base_url = $base_url;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);    
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT_MS, self::HTTP_TIMEOUT);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, false); // Connection-pool for CURL

    $this->curl = $curl;
    
  }
  
  /**
  * HTTP GET
  */
  function http_get($uri, $data = array()) {
    curl_setopt($this->curl, CURLOPT_URL, $this->get_full_url($uri));    
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "GET"); 
    return $response = curl_exec($this->curl);    
  }  
  
  /**
  * HTTP PUT
  */
  function http_put($uri, $data = array()) {
    curl_setopt($this->curl, CURLOPT_URL, $this->get_full_url($uri));    
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "PUT"); 
    return $response = curl_exec($this->curl);    
  }

  /**
  * HTTP DELETE
  */
  function http_delete($uri, $data = array()) {
    curl_setopt($this->curl, CURLOPT_URL, $this->get_full_url($uri));
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
    return $response = curl_exec($this->curl);
  }
  
  /**
  * Get full URL from partial one
  */
  private function get_full_url($uri) {
    $uri = rawurlencode($uri);    
    return $this->base_url . '/' . $uri;
  }
}
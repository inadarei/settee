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
  
  /**
  * Class constructor
  */
  function __construct($base_url) {
    $this->base_url = $base_url;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);    
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT_MS, self::HTTP_TIMEOUT);

    $this->curl = $curl;
    
  }
  
  /**
  * HTTP GET
  */
  function get($uri, $data = array()) {
    curl_setopt($this->curl, CURLOPT_URL, $this->get_full_url($uri));    
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "GET"); 
    return $response = curl_exec($this->curl);    
  }  
  
  /**
  * HTTP PUT
  */
  function put($uri, $data = array()) {
    curl_setopt($this->curl, CURLOPT_URL, $this->get_full_url($uri));    
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "PUT"); 
    return $response = curl_exec($this->curl);    
  }

  /**
  * HTTP DELETE
  */
  function delete($uri, $data = array()) {
    curl_setopt($this->curl, CURLOPT_URL, $this->get_full_url($uri));
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
    return $response = curl_exec($this->curl);
  }
  
  /**
  * Get full URL from partial one
  */
  private function get_full_url($uri) {
    return $this->base_url . '/' . $uri;
  }
}
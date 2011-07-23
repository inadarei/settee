<?php

require_once (realpath(dirname(__FILE__) . '/../src/settee.php'));
require_once (dirname(__FILE__) . '/SetteeTestCase.class.php');

class SetteeRestClientTest extends SetteeTestCase {

  private $rest_client;
  
  public function setUp() {
    parent::setUp();
    $this->rest_client = SetteeRestClient::get_instance($this->db_url);
  }

  public function test_get_full_url() {

    //-- Can't run this test in PHP versions earlier than 5.3.2, which do not support ReflectionMethod class.
    if (!class_exists('ReflectionMethod')) {
      return;
    }

    //-- Prepare for testing the private full_url_method method.
    $get_full_url_method = new ReflectionMethod('SetteeRestClient', 'get_full_url');
    $get_full_url_method->setAccessible(TRUE);

    $uri = 'irakli/26cede9ab9cd8fcd67895eb05200d1ea';
    //-- Equivalent to: $calc = $this->rest_client->get_full_url($uri); but for a private method.
    $calc = $get_full_url_method->invokeArgs($this->rest_client, array($uri));
    //--
    $expected = $this->db_url . '/irakli/26cede9ab9cd8fcd67895eb05200d1ea';
    $this->assertEquals($calc, $expected, "Full URL Generation with DB and ID");

    $uri = 'irakli/26cede9ab9cd8fcd67895eb05200d1ea?rev=2-21587f7dffc43b4100f40168f309a267';
    $calc = $get_full_url_method->invokeArgs($this->rest_client, array($uri));
    $expected = $this->db_url . '/irakli/26cede9ab9cd8fcd67895eb05200d1ea?rev=2-21587f7dffc43b4100f40168f309a267';
    $this->assertEquals($calc, $expected, "Full URL Generation with DB, ID and Single Query Parameter");
    
    $uri = 'irakli/26cede9ab9cd8fcd67895eb05200d1ea?rev=2-21587f7dffc43b4100f40168f309a267&second=foo';
    $calc = $get_full_url_method->invokeArgs($this->rest_client, array($uri));
    $expected = $this->db_url . '/irakli/26cede9ab9cd8fcd67895eb05200d1ea?rev=2-21587f7dffc43b4100f40168f309a267&second=foo';
    $this->assertEquals($calc, $expected, "Full URL Generation with DB, ID and Two Query Parameters");

  }


}


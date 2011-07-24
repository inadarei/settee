<?php

require_once (realpath(dirname(__FILE__) . '/../src/settee.php'));
require_once (dirname(__FILE__) . '/SetteeTestCase.class.php');

class SetteeDatabaseTest extends SetteeTestCase {

  private $db;

  public function setUp() {
    parent::setUp();
    $dbname = "settee_tests_" . md5(microtime(true));
    $this->db = $this->server->get_db($dbname);
    $this->server->create_db($this->db);
  }

  public function test_document_lifecycle_objectbased() {
    $doc = new StdClass();
    $doc->firstName = "Irakli";
    $doc->lastName = "Nadareishvili";
    $doc->IQ = 200;
    $doc->hobbies = array("skiing", "swimming");
    $doc->pets = array ("whitey" => "labrador", "mikey" => "pug");

    $doc = $this->db->save($doc);
    $this->assertTrue(!empty($doc->_id) && !empty($doc->_rev), "Document creation success [object-based]");

    $_rev = $doc->_rev;
    $doc = $this->db->get($doc->_id);
    $this->assertEquals($_rev, $doc->_rev, "Document retrieval success [object-based] test");

    $doc->firstName = "Ika";
    $db_doc = $this->db->save($doc);
    $this->assertEquals($doc->firstName, $db_doc->firstName, "Document update success [object-based]");

    $this->db->delete($doc);


    try {
      $doc = $this->db->get($doc->_id);
    } catch (SetteeRestClientException $e) {
      // we expect exception to fire, so this is good.
      return;
    }

    $this->fail('Document still available for retrieval after being deleted. [object-based]');
  }

    // Should work with json string as well:
    //


  public function test_document_lifecycle_jsonbased() {
    $doc = '{"firstName":"Irakli","lastName":"Nadareishvili","IQ":200,"hobbies":["skiing","swimming"],"pets":{"whitey":"labrador","mikey":"pug"}}';

    $doc = $this->db->save($doc);
    $this->assertTrue(!empty($doc->_id) && !empty($doc->_rev), "Document creation success [json-based]");

    $_rev = $doc->_rev;

    //$db_rev = $this->db->get_rev($doc->_id);
    //$this->assertEquals($_rev, $db_rev, "Document Revision retrieval success [json-based] test");
    
    $db_doc = $this->db->get($doc->_id);
    $this->assertEquals($_rev, $db_doc->_rev, "Document retrieval success [json-based] test");

    $doc = '{';
    $doc .= '"_id":"' . $db_doc->_id . '",';
    $doc .= '"_rev":"' . $db_doc->_rev . '",';
    $doc .= '"firstName":"Ika","lastName":"Nadareishvili","IQ":200,"hobbies":["skiing","swimming"],"pets":{"whitey":"labrador","mikey":"pug"}}';
    
    $orig_doc = json_decode($doc);
    $db_doc = $this->db->save($doc);
    $this->assertEquals($orig_doc->firstName, $db_doc->firstName, "Document update success [json-based]");

    $doc = '{';
    $doc .= '"_id":"' . $db_doc->_id . '",';
    $doc .= '"_rev":"' . $db_doc->_rev . '",';
    $doc .= '"firstName":"Ika","lastName":"Nadareishvili","IQ":200,"hobbies":["skiing","swimming"],"pets":{"whitey":"labrador","mikey":"pug"}}';

    $this->db->delete($doc);

    try {
      $doc = $this->db->get($db_doc->_id);
    } catch (SetteeRestClientException $e) {
      // we expect exception to fire, so this is good.
      return;
    }

    $this->fail('Document still available for retrieval after being deleted. [object-based]');
  }

  public function test_invalid_document() {
    $doc = 12345;
    try {
      $doc = $this->db->save($doc);
    } catch (SetteeRestClientException $e) {
      // we expect exception to fire, so this is good.
      return;
    }

    $this->fail('Document saved with invalid format');
  }

  public function test_inline_attachment_json() {
    $doc = '{
              "_id":"attachment_doc",
              "_attachments":
              {
                "foo.txt":
                {
                  "content_type":"text\/plain",
                  "data": "VGhpcyBpcyBhIGJhc2U2NCBlbmNvZGVkIHRleHQ="
                }
              }
            }';
    $db_doc = $this->db->save($doc);
    $this->assertTrue(is_object($db_doc->_attachments), "Inline attachment save successful [json-based]");
  }

  public function test_inline_attachment_obj_content() {
    $doc = new stdClass();
    $doc->_id = "attachment_doc";
    $this->db->add_attachment($doc, "foo.txt", "This is some text to be encoded", "text/plain");
    $db_doc = $this->db->save($doc);
    $this->assertTrue(is_object($db_doc->_attachments), "Inline attachment save successful [object-based]");

    $doc = new stdClass();
    $doc->_id = "attachment_doc_autodetect";
    $this->db->add_attachment($doc, "foo.txt", "This is some other text to be encoded");
    $db_doc = $this->db->save($doc);
    $this->assertTrue(is_object($db_doc->_attachments), "Inline attachment save successful [object-based, mime auto-detection]");
  }

  public function tearDown() {
    $ret = $this->server->drop_db($this->db);
  }

}


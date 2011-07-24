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

  public function test_get_rev() {
    $doc = new stdClass();
    $doc->_id = "some_fixed_id";
    $doc = $this->db->save($doc);

    $_rev = $doc->_rev;

    $db_rev = $this->db->get_rev($doc->_id);
    $this->assertEquals($_rev, $db_rev, "Document Revision retrieval success");

    
    $doc->_id = "some_fixed_id";
    $doc->title = "Some Fixed ID";
    $doc = $this->db->save($doc);

    $_rev = $doc->_rev;

    $db_rev = $this->db->get_rev($doc->_id);
    $this->assertEquals($_rev, $db_rev, "Document Revision retrieval success after re-save");

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

  public function test_inline_attachment_obj_file() {
    $doc = new stdClass();
    $doc->_id = "attachment_doc";
    $file_path = dirname(__FILE__) . "/resources/couch-logo.pdf";
    $this->db->add_attachment_file($doc, "foo.pdf", $file_path, "application/pdf");
    $db_doc = $this->db->save($doc);
    $this->assertTrue(is_object($db_doc->_attachments), "Inline attachment of file successful");

    $doc = new stdClass();
    $doc->_id = "attachment_doc_autodetect";
    $file_path = dirname(__FILE__) . "/resources/couch-logo.pdf";
    $this->db->add_attachment_file($doc, "foo.pdf", $file_path);
    $db_doc = $this->db->save($doc);
    $this->assertTrue(is_object($db_doc->_attachments), "Inline attachment of file successful w/ mime type auto-detection");
  }

  public function test_view_lifecycle() {
    $this->_create_some_sample_docs();
    
  $map_src = <<<VIEW
function(doc) {
  if(doc.date && doc.title) {
    emit(doc.date, doc.title);
  }
}
VIEW;

    $view = $this->db->save_view("foo_views", "bar_view", $map_src);
    $this->assertEquals("_design/foo_views", $view->_id, "View Creation Success");
    
    $view = $this->db->get_view("foo_views", "bar_view");
    $this->assertEquals(3, $view->total_rows, "Running a View Success");

  }

  /**
   * Create some sample docs for running tests on them.
   *
   * <p>This sample was taken from a wonderful book:
   *  CouchDB: The Definitive Guide (Animal Guide) by J. Chris Anderson, Jan Lehnardt and Noah Slater
   *  http://www.amazon.com/CouchDB-Definitive-Guide-Relax-Animal/dp/0596155891/ref=sr_1_1?ie=UTF8&qid=1311533443&sr=8-1
   * 
   * @return void
   */
  private function _create_some_sample_docs() {
    $doc = new stdClass();
    $doc->_id = "biking";
    $doc->title = "Biking";
    $doc->body = "My biggest hobby is mountainbiking";
    $doc->date =  "2009/01/30 18:04:11";
    $this->db->save($doc);

    $doc = new stdClass();
    $doc->_id = "bought-a-cat";
    $doc->title = "Bought a Cat";
    $doc->body = "I went to the the pet store earlier and brought home a little kitty...";
    $doc->date =  "2009/02/17 21:13:39";
    $this->db->save($doc);

    $doc = new stdClass();
    $doc->_id = "hello-world";
    $doc->title = "Hello World";
    $doc->body = "Well hello and welcome to my new blog...";
    $doc->date = "2009/01/15 15:52:20";
    $this->db->save($doc);
  }

  public function tearDown() {
    $ret = $this->server->drop_db($this->db);
  }

}


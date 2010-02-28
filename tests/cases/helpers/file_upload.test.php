<?php
App::import('Helper', 'FileUpload.FileUpload');
App::import('Helper', 'Html');
App::import('Config', 'FileUpload.file_upload_settings');
class FileUploadHelperTest extends CakeTestCase {
  var $FileUpload = null;

  function startTest(){
    $this->FileUpload = new FileUploadHelper();
    $this->FileUpload->Html = new HtmlHelper();
  }
  
  function testImage(){
    $results = $this->FileUpload->image('some_image.jpg');
    $this->assertEqual('some_image.jpg', $this->FileUpload->fileName);
    $this->assertFalse($results); //file doesn't exist
  }
  
  function testDefaultSettings(){
    $results = $this->FileUpload->settings;
    $DefaultSettings = new FileUploadSettings();
    $expected = $DefaultSettings->defaults;
    $this->assertEqual($expected['fileModel'], $results['fileModel']);
    $this->assertEqual($expected['fileVar'], $results['fileVar']);
    $this->assertEqual($expected['allowedTypes'], $results['allowedTypes']);
    $this->assertEqual($expected['fields'], $results['fields']);
    $this->assertEqual($expected['massSave'], $results['massSave']);
    $this->assertEqual($expected['automatic'], $results['automatic']);
    
    //change uploadDir on the fly.
    $results = $this->FileUpload->image('ignore', array('uploadDir' => 'something/different'));
    $this->assertEqual('something/different', $this->FileUpload->settings['uploadDir']);
  }
    
  function endTest(){
    unset($this->FileUpload);
  }
}
?>
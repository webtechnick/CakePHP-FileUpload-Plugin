<?php
App::import('Behavior', 'FileUpload.FileUpload');
class TestUpload extends CakeTestModel {
  var $name = 'Upload';
  var $data = null;
  var $useDbConfig = 'test_suite';
  var $useTable = false;
  
  function save($data){
    $this->data = $data;
  }
}

class FileUploadTest extends CakeTestCase {

  function startTest(){
    $this->FileUpload = new FileUploadBehavior();
    $this->Upload = new TestUpload();
  }
  
  
  function testSetupShouldLoadDefaults(){
    $this->FileUpload->setUp($this->Upload);
    $options = $this->FileUpload->Uploader->options;
    
    $this->assertEqual('Upload', $options['fileModel']);
    $this->assertTrue(strpos($options['uploadDir'], 'webroot/files'));
    $this->assertEqual('file', $options['fileVar']);
    $this->assertFalse($options['massSave']);
    $this->assertFalse($options['maxFileSize']);
    $this->assertFalse($options['required']);
    $this->assertFalse($options['fileNameFunction']);
    $this->assertTrue($options['automatic']);
    $this->assertTrue($options['unique']);
  }
  
  function testSetupShouldLoadSettionsOnTheFly(){
    $model_options = array(
      'fileVar' => 'var',
      'uploadDir' => 'uploads',
      'required' => true,
      'maxFileSize' => 10000,
      'fileNameFunction' => 'sha1',
      'fileModel' => 'NotValidModel' //should ignore, and set model name to appropriate model
    );
    $this->FileUpload->setUp($this->Upload, $model_options);
    $options = $this->FileUpload->Uploader->options;
    
    $this->assertEqual('Upload', $options['fileModel']);
    $this->assertTrue(strpos($options['uploadDir'], 'webroot/uploads'));
    $this->assertEqual('var', $options['fileVar']);
    $this->assertFalse($options['massSave']);
    $this->assertEqual(10000, $options['maxFileSize']);
    $this->assertTrue($options['required']);
    $this->assertEqual('sha1', $options['fileNameFunction']);
    $this->assertTrue($options['automatic']);
    $this->assertTrue($options['unique']);
  }
  
  function endTest(){
    unset($this->FileUpload, $this->Upload);
  }
}
?>
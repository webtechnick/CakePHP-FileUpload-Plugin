<?php
App::import('Config', 'FileUpload.file_upload_settings');
App::import('Vendor', 'FileUpload.Uploader');
class Upload extends CakeTestModel {
  var $name = 'Upload';
  var $data = null;
  var $useDbConfig = 'test_suite';
  var $useTable = false;
  
  function save($data){
    $this->data = $data;
  }
}

class UploadWithCallback extends CakeTestModel {
  var $name = 'Upload';
  var $data = null;
  var $useDbConfig = 'test_suite';
  var $useTable = false;
  
  function save($data){
    $this->data = $data;
  }
  
  function fileNameCallback($fileName){
    return "filenameCalledBack";
  }
}

class UploadWithFalseCallback extends CakeTestModel {
  var $name = 'Upload';
  var $data = null;
  var $useDbConfig = 'test_suite';
  var $useTable = false;
  
  function save($data){
    $this->data = $data;
  }
  
  function fileNameCallback($fileName){
    return false;
  }
}

class UploaderTest extends CakeTestCase {
  
  function mockFile(){
    return array(
      'name' => 'file.jpg',
      'type' => 'image/jpeg',
      'size' => 1000,
      'error' => 0,
      'tmp_name' => 'asdfasdf'
    ); 
  }

  function startTest(){
    $FileUploadSettings = new FileUploadSettings;
    $this->Uploader = new Uploader($FileUploadSettings->defaults);
  }
  
  function testHandleFileNameCallback(){
    $this->Uploader->options['fileNameFunction'] = 'fileNameCallback';
    $this->Uploader->options['fileModel'] = 'UploadWithCallback';
    
    $result = $this->Uploader->__handleFileNameCallback('tocallback');
    $this->assertEqual('filenameCalledBack', $result);
    
    $this->Uploader->options['fileNameFunction'] = false;
    $this->Uploader->options['fileModel'] = 'Upload';
    
    $result = $this->Uploader->__handleFileNameCallback('leavealone');
    $this->assertEqual('leavealone', $result);
  }
  
  function testHandleUnique(){
    $this->Uploader->file = array(
      'name' => 'file.jpg'
    );
    $this->assertEqual('file.jpg', $this->Uploader->__handleUnique('file.jpg'));
  }
  
  function testSetFile(){
    $file = $this->mockFile();
    $this->Uploader->setFile($file);
    $this->assertEqual($file, $this->Uploader->file);
    
    $this->Uploader->setFile(null);
    $this->assertEqual($file, $this->Uploader->file);
  }
  
  function testCheckType(){
    $file = $this->mockFile();
    $this->Uploader->options['allowedTypes'] = array(
      'jpg' => array('image/jpeg', 'image/pjpeg'),
      'png' => array('image/png'),
      'gif'
    );
    
    $this->assertTrue($this->Uploader->checkType($file));
    
    $file['type'] = 'notjpeg'; //not ignored
    $this->assertFalse($this->Uploader->checkType($file));
    
    $file['name'] = 'file.gif';
    $file['type'] = 'notjpeg'; //ignored
    $this->assertTrue($this->Uploader->checkType($file));
  }
  
  function testCheckFileWithVlidFile(){
    $file = $this->mockFile();
    $this->assertTrue($this->Uploader->checkFile($file));
    $this->assertTrue($this->Uploader->checkFile());
  }
  
  function testCheckFileWithInVlidFile(){
    $this->assertFalse($this->Uploader->checkFile());
    
    $file = $this->mockFile();
    $file['error'] = 2;
    $this->assertFalse($this->Uploader->checkFile($file));
    $this->assertEqual('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', $this->Uploader->errors[0]);
  }
  
  function testCheckSize(){
    $file = $this->mockFile();
    $this->Uploader->options['maxFileSize'] = 10000;
    
    $this->assertTrue($this->Uploader->checkSize($file));
    
    $this->Uploader->options['maxFileSize'] = 100;
    $this->assertFalse($this->Uploader->checkSize($file));
    
    $this->Uploader->options['maxFileSize'] = false;
    $this->asserttrue($this->Uploader->checkSize($file));
  }
  
  function testExt(){
    $this->assertEqual('.jpg', $this->Uploader->_ext(array('name' => 'file.jpg')));
    $this->assertEqual('.png', $this->Uploader->_ext(array('name' => 'file.png')));
    $this->assertEqual('.sft', $this->Uploader->_ext(array('name' => 'file.sft')));
  }
  
  function endTest(){
    unset($this->Uploader);
  }
}
?>
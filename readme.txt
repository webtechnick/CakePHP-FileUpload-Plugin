AUTHOR: Nick Baker
VERSION: 3.0
EMAIL: nick@webtechnick.com

INSTALL:
copy the file_upload directory into your app/plugins/ directory

SVN:
svn checkout https://svn2.xp-dev.com/svn/nurvzy-file-upload-plugin file_upload

DOWNLOAD:
http://projects.webtechnick.com/file_upload_plugin

BLOG ARTICLE:
http://www.webtechnick.com/blogs/view/221/CakePHP_File_Upload_Plugin

BAKERY ARTICLE:  
For More documentation visit the bakery @ http://bakery.cakephp.org/articles/view/file-upload-component-w-automagic-model-optional

CHANGELOG:
   3.0: Converted Component and Helper into a plugin for easy management between projects
   2.0.1: Bug Fixes
   2.0: Release of FileUploadHelper
   1.7: Added detailed errors to FileUploadComponent
   1.6: Bug Fixes
   1.5: Bug Fixes
   1.4: Added toggle to allow for auto processFile or not. 
   1.3: Bug Fixes
   1.2: Bug Fixes
   1.1: Converted to cakePHP naming conventions and standards
   1.0: Initial Release

=============================== SETUP AND BASIC CONFIGURATIONS ============================

You'll need to add the FileUpload.FileUpload in both the components and helpers array

<?php
var $helpers = array('Html', 'Form', 'FileUpload.FileUpload');
var $components = array('FileUpload.FileUpload');
?>

Upon submitting a file the FileUpload Component will automatically search for your uploaded file, verify its of the proper type set by $this->FileUpload->allowedTypes:
<?php 
function beforeFilter(){
  parent::beforeFilter();
  //defaults to 'image/jpeg','image/gif','image/png','image/pjpeg','image/x-png'
  $this->FileUpload->allowedTypes = array('image/jpeg','text/plain'); 
}
?>

Then it will attempt to copy the file to your uploads directory set by $this->FileUpload->uploadDir:
<?php 
function beforeFilter(){
  parent::beforeFilter();
  //defaults to 'files', will be webroot/files, make sure webroot/files exists and is chmod 777
  $this->FileUpload->uploadDir = 'files'; 
}
?>


=============================== MODEL CONFIGURATIONS ===============================
You can use this Component with or without a model. It defaults to use the Upload model:

<?php
//app/models/upload.php 
class Upload extends AppModel{
  var $name = 'Upload';
}
?>

==== SQL EXAMPLE IF YOUR USING A MODEL ====
If you're using a Model, you'll need to have at least 3 fields to hold the uploaded data (name, type, size)
Example SQL Table:
-- 
-- Table structure for table `uploads`
-- 

CREATE TABLE IF NOT EXISTS `uploads` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(200) NOT NULL,
  `type` varchar(200) NOT NULL,
  `size` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

Optionally, you can run the schema in a terminal like so:
cake schema run create -path plugins\file_upload\config\sql -name upload

Default fields are name, type, and size; but you can change that at anytime using the $this->FileUpload->fields = array();
<?php 
function beforeFilter(){
  parent::beforeFilter();
  //fill with associated array of name, type, size to the corresponding column name
  $this->FileUpload->fields = array('name'=> 'file_name', 'type' => 'file_type', 'size' => 'file_size');
}
?>

==== VIEW WITH MODEL ====
Example view WITH Model WITH Helper:
<?= $fileUpload->input(); ?>

Example View WITH Model WITHOUT Helper:
<?= $form->create('Upload', array('type'=>'file')); ?>
<?= $form->input('file', array('type'=>'file')); ?>
<?= $form->end('Submit')); ?>




=============================== WITHOUT MODEL CONFIGURATION =================================

If you wish to NOT use a model simply set $this->FileUpload->fileModel = null; in a beforeFilter.
<?php 
  //in a controller
  function beforeFilter(){
    parent::beforeFilter();
    $this->FileUpload->fileModel = null;  //Upload by default.
  }
?>

==== VIEW WITHOUT MODEL ====
Example View WITHOUT a Model:
<form action="controller/action" method="post" enctype="multipart/form-data">
<input type="file" name="file" />
<input type="submit" name="Submit" />
</form>


Example view WITHOUT Model WITH Helper:
<?= $fileUpload->input(array('var' => 'file', 'model' => false)); ?>





======================= CONTROLLER EXAMPLES =======================
If a fileModel is given, it will attempt to save the record of the uploaded file to the database for later use. Upon success the FileComponent sets $this->FileUpload->success to TRUE; You can use this variable to test in your controller like so:

<?php 
class UploadsController extends AppController {

  var $name = 'Uploads';
  var $helpers = array('Html', 'Form', 'FileUpload.FileUpload');
  var $components = array('FileUpload.FileUpload');
  
  function admin_add() {
    if(!empty($this->data)){
      if($this->FileUpload->success){
        $this->set('photo', $this->FileUpload->finalFile);
      }else{
        $this->Session->setFlash($this->FileUpload->showErrors());
      }
    }
  }
}
?>

At any time you can remove a file by using the $this->FileUpload->removeFile($name); function. An example of that being used might be in a controller:
<?php 
class UploadsController extends AppController {

  var $name = 'Uploads';
  var $helpers = array('Html', 'Form', 'FileUpload.FileUpload');
  var $components = array('FileUpload.FileUpload');
  
  function admin_delete($id = null) {
    $upload = $this->Upload->findById($id);
    if($this->FileUpload->removeFile($upload['Upload']['name'])){
      if($this->Upload->del($id)){
        $this->Session->setFlash('Upload deleted');
        $this->redirect(array('action'=>'index'));
      }
    }
  }
}
?>

======================== VIEWING AN UPLOADED PHOTO EXAMPLES ========================
To View the photo variable you might type something like
$html->image("/files/$photo");

Example with FileUploadHelper:
$fileUpload->image($photo);


Simple as that. Automagic File Uploading. I hope you enjoy it. If you read through the documentation I've written in the actual FileUpload Component it will give you detailed examples and explanations of each variable/function. Comments are appreciated.


ADDITIONAL CONTRIBUTIONS:

@Elmer 2/9/2009 (http://bakery.cakephp.org/articles/view/file-upload-component-w-automagic-model-optional):
"As long as the automatic var is true (default), the component works as always. But if it is set to false, processFile() is no longer called automatically. From my controller I can now set an upload folder and call processFile() when I'm ready."

if ($this->FileUpload->hasFile) {
    $this->FileUpload->uploadDir = 'files/sub/dir/1/2/3';
    $this->FileUpload->processFile();
} 

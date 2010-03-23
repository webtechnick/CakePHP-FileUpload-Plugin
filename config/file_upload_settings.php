<?php
class FileUploadSettings {

  /**
    * File Upload Settings Defaults.  You can change these here or on the fly within each component/behaviour
    * 
    * Not all settings are for for both components and behaviors, as such, each is labeled.
    *
    * Behavior settings overwrite example:
    *   var $actsAs = array(
    *     'FileUpload.FileUpload' => array(
    *       'uploadDir' => 'files',
    *       'fields' => array('name' => 'file_name', 'type' => 'file_type', 'size' => 'file_size'),
    *       'allowedTypes' => array('application/pdf')
    *     )
    *    )
    * 
    *  Component settings overwrite example:
    *   function beforeFilter(){
    *     parent::beforeFilter();
    *     $this->FileUpload->uploadDir('files');
    *     $this->FileUpload->fields(array('name' => 'file_name', 'type' => 'file_type', 'size' => 'file_size'));
    *     $this->FileUpload->fileModel('Upload');
    *     $this->FileUpload->allowedTypes(array('application/pdf'));
    *   }
    */
  var $defaults = array(
    /**
      * Component Setting Only.
      *
      * fileModel is the name of the model used if we want to 
      *  keep records of uploads in a database.
      * 
      * if you don't wish to use a database, simply set this to null in a controller
      *  $this->FileUpload->fileModel = null;
      */
    'fileModel' => 'Upload',
    
    /**
      * Component and Behavior Setting.
      *
      * fileVar is the name of the key to look in for an uploaded file
      * For this to work you will need to use the
      * $form-input('Upload.file', array('type'=>'file)); 
      *
      * If you are NOT using a model the input must be just the name of the fileVar
      * input type='file' name='file'
      */
    'fileVar' => 'file',
    
    /**
      * Component and Behavior Setting.
      * 
      * uploadDir is the directory name in the webroot that you want
      * the uploaded files saved to.  default: files which means
      * webroot/files must exist and set to chmod 777
      */
    'uploadDir' => 'files',
    
    /**
      * Component and Behavior Setting.
      * 
      * allowedTypes is the allowed types of files that will be saved
      * to the filesystem.  You can change it at anytime without
      * $this->FileUpload->allowedTypes = array('text/plain',etc...);
      */
    'allowedTypes' => array('image/jpeg','image/gif','image/png','image/pjpeg','image/x-png'),
    
    /**
      * Max file size in bytes
      * @var mixed false ignore maxFileSize (php.ini limit). int bytes of max file size
      */
    'maxFileSize' => false,
      
    /**
      * Component and Behavior Setting.
      * 
      * fields are the fields relating to the database columns
      */
    'fields' => array('name'=>'name','type'=>'type','size'=>'size'),
    
    /**
      * Component Setting Only.
      * 
      * massSave is used if you'd like the plugin to handle associative records
      * along with just the Uploaded data.  By default this is turned off.
      * Turning this feature on will require you to have your model associations
      * set correctly in your Upload model.
      */
    'massSave' => false,
    
    /**
      * Component Setting Only.
      * 
      * automatic determines if the process of all files will be called automatically upon detection.
      * if true: files are processed as soon as they come in
      * if false: when a file is ready hasFile is set to true
      * it is then up to the calling application to call processAllFiles()
      * whenever it wants. this allows params to be changed per uploaded file
      * (save every file in a different folder for instance)
      */
    'automatic' => true 
  );

}
?>
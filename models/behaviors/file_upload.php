<?php
/**
  * Behavior for file uploads
  * 
  * Example Usage:
  *  var $actsAs = array(
  *     'FileUpload.FileUpload' => array(
  *       'uploadDir' => 'files',
  *       'fields' => array('name' => 'file_name', 'type' => 'file_type', 'size' => 'file_size'),
  *       'allowedTypes' => array('application/pdf')
  *     )
  *    )
  *
  * @version: 4.0.3
  * @author: Nick Baker
  * @link: http://www.webtechnick.com
  */
App::import('Vendor', 'FileUpload.uploader');
App::import('Config', 'FileUpload.file_upload_settings');
class FileUploadBehavior extends ModelBehavior {
  
  /**
    * Uploader is the uploader instance of class Uploader. This will handle the actual file saving.
    */
  var $Uploader = null;
  
  function setUp(&$Model, $options = array()){
    $FileUploadSettings = new FileUploadSettings;
    if(!is_array($options)){
      $options = array();
    }
    $this->options = array_merge($FileUploadSettings->defaults, $options);
        
    $uploader_settings = $this->options;
    $uploader_settings['uploadDir'] = WWW_ROOT . $uploader_settings['uploadDir'];
    $this->Uploader = new Uploader($uploader_settings);
  }
 
  /**
    * beforeSave if a file is found, upload it, and then save the filename according to the settings
    *
    */
  function beforeSave(&$Model){
    if(isset($Model->data[$Model->alias][$this->options['fileVar']])){
      $file = $Model->data[$Model->alias][$this->options['fileVar']];
      $this->Uploader->file = $file;
      
      if($this->Uploader->hasUpload()){
        $fileName = $this->Uploader->processFile();
        if($fileName){
          $Model->data[$Model->alias][$this->options['fields']['name']] = $fileName;
          $Model->data[$Model->alias][$this->options['fields']['size']] = $file['size'];
          $Model->data[$Model->alias][$this->options['fields']['type']] = $file['type'];
        } else {
          return false; // we couldn't save the file, return false
        }
        unset($Model->data[$Model->alias][$this->options['fileVar']]);
      }
      else {
        unset($Model->data[$Model->alias]);
      }
    }
    return true;
  }
  
  /**
    * Updates validation errors if there was an error uploading the file.
    * presents the user the errors.
    */
  function beforeValidate(&$Model){
    $file = $Model->data[$Model->alias][$this->options['fileVar']];
    $this->Uploader->file = $file;
    if($this->Uploader->hasUpload()){
      if($this->Uploader->checkFile() && $this->Uploader->checkType()){
        $Model->beforeValidate();
      }
      else {
        $Model->validationErrors[$this->options['fileVar']] = $this->Uploader->showErrors();
      }
    }
    return $Model->beforeValidate();
  }
  
  /**
    * Automatically remove the uploaded file.
    */
  function beforeDelete(&$Model, $cascade){
    $Model->recursive = -1;
    $data = $Model->read();
    
    $this->Uploader->removeFile($data[$Model->alias][$this->options['fields']['name']]);
    return true;
  }
  
}
?>
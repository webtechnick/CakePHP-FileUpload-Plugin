<?php
/**
  * Behavior for file uploads
  * 
  * Example Usage:
  *
  * @example 
  *   var $actsAs = array('FileUpload.FileUpload');
  *
  * @example 
  *   var $actsAs = array(
  *     'FileUpload.FileUpload' => array(
  *       'uploadDir'    => WEB_ROOT . DS . 'files',
  *       'fields'       => array('name' => 'file_name', 'type' => 'file_type', 'size' => 'file_size'),
  *       'allowedTypes' => array('pdf' => array('application/pdf')),
  *       'required'    => false,
  *       'unique' => false //filenames will overwrite existing files of the same name. (default true)
  *       'fileNameFunction' => 'sha1' //execute the Sha1 function on a filename before saving it (default false)
  *     )
  *    )
  *
  *
  * @note: Please review the plugins/file_upload/config/file_upload_settings.php file for details on each setting.
  * @version: since 6.1.0
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
  
  /**
    * Setup the behavior
    */
  function setUp(&$Model, $options = array()){
    $FileUploadSettings = new FileUploadSettings;
    if(!is_array($options)){
      $options = array();
    }
    $this->options = array_merge($FileUploadSettings->defaults, $options);
        
    $uploader_settings = $this->options;
    $uploader_settings['uploadDir'] = $this->options['forceWebroot'] ? WWW_ROOT . $uploader_settings['uploadDir'] : $uploader_settings['uploadDir']; 
    $uploader_settings['fileModel'] = $Model->alias;
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
    return $Model->beforeSave();
  }
  
  /**
    * Updates validation errors if there was an error uploading the file.
    * presents the user the errors.
    */
  function beforeValidate(&$Model){
    if(isset($Model->data[$Model->alias][$this->options['fileVar']])){
      $file = $Model->data[$Model->alias][$this->options['fileVar']];
      $this->Uploader->file = $file;
      if($this->Uploader->hasUpload()){
        if($this->Uploader->checkFile() && $this->Uploader->checkType() && $this->Uploader->checkSize()){
          $Model->beforeValidate();
        }
        else {
          $Model->validationErrors[$this->options['fileVar']] = $this->Uploader->showErrors();
        }
      }
    }
    elseif(isset($this->options['required']) && $this->options['required']){
      $Model->validationErrors[$this->options['fileVar']] = 'No File';
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
    return $Model->beforeDelete($cascade);
  }
  
}
?>
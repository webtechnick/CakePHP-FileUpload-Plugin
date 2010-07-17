<?php
/**
* FileUpload Component
*
* Manages uploaded files to be saved to the file system.
*
* @copyright    Copyright 2009, Webtechnick
* @link         http://www.webtechnick.com
* @author       Nick Baker
* @version      4.0.4
* @license      MIT
*/
App::import('Vendor', 'FileUpload.uploader');
App::import('Config', 'FileUpload.file_upload_settings');
class FileUploadComponent extends Object{
  /**
    * options are the default options that will be used
    * 
    * Settings in config/file_upload_settings.php
    */
  var $options = array();
  
  /**
    * Uploader
    */
  var $Uploader = null;
  
  /**
    * uploadDetected will be true if an upload is detected even
    * if it can't be processed due to misconfiguration
    *
    * @var boolean
    * @access public
    */
  var $uploadDetected = false;
  
  /**
    * uploadedFiles will hold the uploadedFiles array if there is one, or multiple
    *
    * @var boolean|array
    * @access public
    */
  var $uploadedFiles = false;
  
  /**
    * currentFile will hold the currentFile being used array if there is one
    *
    * @var boolean|array
    * @access public
    */
  var $currentFile = false;
  
  /**
   * hasFile will be true if an upload is pending and needs to be processed
   * 
   * @contributer Elmer (http://bakery.cakephp.org/articles/view/file-upload-component-w-automagic-model-optional)
   * @var boolean
   * @access public
   */
  var $hasFile = false;
  
  /**
    * data and params are the controller data and params
    *
    * @var array
    * @access public
    */
  var $data = array();
  var $params = array();
  
  /**
    * Final file is set on move_uploadedFile success.
    * This is the file name of the final file that was uploaded
    * to the uploadDir directory.
    *
    * @var array of strings showing the final file name
    * @access public
    */
  var $finalFiles = array();
  
  /**
    * success is set if we have a fileModel and there was a successful save
    * or if we don't have a fileModel and there was a successful file uploaded.
    *
    * @var boolean
    * @access public
    */
  var $success = false;
  
  /**
    * uploadIds is the final database ids saved when files are detected
    * @var array of ids of single or multiple files uploaded
    * @access public
    */
  var $uploadIds = array();
  
  /**
    * errors holds any errors that occur as string values.
    * this can be access to debug the FileUploadComponent
    *
    * @var array
    * @access public
    */
  var $errors = array();
  
  /**
    * Overloaded call method
    *
    * @param string $method Name of method called
    * @param mixed $params Params for method.
    * @return mixed
    */
  function __call($method, $params){
    if(key_exists($method, $this->options)){
      array_unshift($params, $method);
			return $this->dispatchMethod('attr', $params);
    }
  }
  
  /**
    * attr will take a name, if only the name is given it will return the corresponding options
    * if a value is given it will set the option to the given value.
    *
    * @param string name of the given option
    * @param string value to set to name option, if null, return option's value
    * @return mixed option on key name, or void if setting
    */
  function attr($name, $values = null){
    if(key_exists($name, $this->options)){
      if(func_num_args() > 1){
        $this->options[$name] = $values;
      }
      else {
        return $this->options[$name];
      }
    }
    else {
      $this->_error("Unknown option: $name");
    }
  }

  /**
    * Initializes FileUploadComponent for use in the controller
    *
    * @param object $controller A reference to the instantiating controller object
    * @return void
    * @access public
    */
  function initialize(&$controller){
    $this->data = $controller->data;
    $this->params = $controller->params;
    
    $FileUploadSettings = new FileUploadSettings;
    $this->options = array_merge($FileUploadSettings->defaults, $this->options);
    
  }
  
  /**
    * Main execution method.  Handles file upload automatically upon detection and verification.
    *
    * @param object $controller A reference to the instantiating controller object
    * @return void
    * @access public
    */
  function startup(&$controller){
    //Backporting 4.0 to 3.6.3 //using setting attributes is now deprecated.
    $this->fileModel = $this->fileModel();
    $this->fileVar = $this->fileVar();
    $this->uploadDir = $this->uploadDir();
    $this->allowedTypes = $this->allowedTypes();
    $this->fields = $this->fields();
    $this->massSave = $this->massSave();
    $this->automatic = $this->automatic();
    
    $uploader_settings = $this->options;
    $uploader_settings['uploadDir'] = $this->options['forceWebroot'] ? WWW_ROOT . $uploader_settings['uploadDir'] : $uploader_settings['uploadDir']; 
    $this->Uploader = new Uploader($uploader_settings);
    
    $this->uploadDetected = ($this->_multiArrayKeyExists("tmp_name", $this->data) || $this->_multiArrayKeyExists("tmp_name",$this->params));
    $this->uploadedFiles = $this->_uploadedFilesArray();
    
    if($this->uploadDetected){
      $this->hasFile = true;
      if($this->options['automatic']) { $this->processAllFiles(); }
    }
    
  }
  
  /**
    * removeFile removes a specific file from the uploaded directory
    *
    * @param string $name A reference to the filename to delete from the uploadDirectory
    * @return boolean
    * @access public
    */
  function removeFile($name = null){
    if(!$name || strpos($name, '://')){
      return false;
    }
    
    $up_dir = $this->options['forceWebroot'] ? WWW_ROOT . $this->options['uploadDir'] : $this->options['uploadDir'];
    $target_path = $up_dir . DS . $name;
    
    //delete main image -- $name
    if(@unlink($target_path)){
      return true;
    } else {
      return false;
    }
  }
  
  /**
    * removeFileById removes a specific file from the uploaded directory when given an id.
    *
    * @param string | int $id A reference to the filename to delete from the uploadDirectory
    * @return boolean
    * @access public
    */
  function removeFileById($id = null){
    if(!$id){
      return false;
    }
    
    $model =& $this->getModel();
    if(!$model){
      $this->_error('FileUpload::removeFileById -- no model detected.');
      return false;
    }
    
    $upload = $model->findById($id);
    $name = $upload[$this->options['fileModel']][$this->options['fields']['name']];
    return $this->removeFile($name);
  }
  
  /**
    * showErrors itterates through the errors array
    * and returns a concatinated string of errors sepearated by
    * the $sep
    *
    * @param string $sep A seperated defaults to <br />
    * @return string
    * @access public
    */
  function showErrors($sep = "<br />"){
    $retval = "";
    foreach($this->errors as $error){
      $retval .= "$error $sep";
    }
    return $retval;
  }
  
  
  /**
    * _processFile takes the detected uploaded file and saves it to the
    * uploadDir specified, it then sets success to true or false depending
    * on the save success of the model (if there is a model).  If there is no model
    * success is meassured on the success of the file being saved to the uploadDir
    *
    * finalFile is also set upon success of an uploaded file to the uploadDir
    *
    * @return void
    * @access public
    */
  function processFile(){
    //Backporting for manual use processFile(), show error when using.
    if(count($this->uploadedFiles) && empty($this->currentFile)){
      $this->_error('FileUpload: You\'re using a deprecated standard of uploading files manually.  Don\'t call processFile() directly. Instead, call processAllFiles().');
      $this->setCurrentFile($this->uploadedFiles[0]);
    }
    
    $save_data = $this->__prepareSaveData();
    
    $this->Uploader->file = $this->currentFile;
    if($finalFile = $this->Uploader->processFile()){
      $this->finalFiles[] = $finalFile;
      $this->finalFile = $finalFile; //backported.  //finalFile is now depreciated
      $save_data[$this->options['fileModel']][$this->options['fields']['name']] = $this->finalFile;
      $save_data[$this->options['fileModel']][$this->options['fields']['type']] = $this->currentFile['type'];
      $save_data[$this->options['fileModel']][$this->options['fields']['size']] = $this->currentFile['size'];
      $model =& $this->getModel();
      
      //Save it
      if(!$model){
        $this->success = true;
      }
      else{
        if($this->options['massSave']){
          if($model->saveAll($save_data)){
            $this->success = true;
            $this->uploadIds[] = $model->id;
            $this->uploadId = $model->id; //backported. //uploadId is now depreciated.
          }
        }
        else{
          if($model->save($save_data)){
            $this->success = true;
            $this->uploadIds[] = $model->id;
            $this->uploadId = $model->id; //backported. //uploadId is now depreciated.
          }
        }
        $model->create(); //get ready for the next one.
      }
    }
    else {
      //add uploader errors to component errors list
      foreach($this->Uploader->errors as $error){
        $this->errors[] = $error;
      }
      $this->_error('FileUpload::processFile() - Unable to save temp file to file system.');
    }
  }
  
  /** __prepareSaveData is used to help generate the array structure depending
    * that relys on $this->options['massSave'] to decide how to structure the save data for
    * the upload.
    *
    * @access private
    * @return array of prepared savedata.
    */
  function __prepareSaveData(){
    $retval = array();
    
    if($this->options['fileModel']){
      $retval = $this->data;
      for($i=0;$i<count($this->uploadedFiles);$i++){
        unset($retval[$this->options['fileModel']][$i]);
      } 
    }
    
    return $retval;
  }
  
  /**
    * process all files that are queued up to be saved to the filesystem or database.
    * 
    * @return void
    * @access public
    */
  function processAllFiles(){
    foreach($this->uploadedFiles as $file){
      $this->_setCurrentFile($file);
      $this->Uploader->file = $this->options['fileModel'] ? $file[$this->options['fileVar']] : $file;
      $this->processFile();
    }
  }
  
  /**
    * Set's the current file to process.
    *
    * @access private
    * @param associative array of file
    * @return void
    */
  function _setCurrentFile($file){
    if($this->options['fileModel']){
      $this->currentFile = $file[$this->options['fileVar']];
    }
    else {
      $this->currentFile = $file;
    }
  }
  
  /**
    * Returns a reference to the model object specified, and attempts
    * to load it if it is not found.
    *
    * @param string $name Model name (defaults to FileUpload::$fileModel)
    * @return object A reference to a model object
    * @access public
    */
	function &getModel($name = null) {
		$model = null;
		if (!$name) {
			$name = $this->options['fileModel'];
		}
    
    if($name){
      if (PHP5) {
        $model = ClassRegistry::init($name);
      } else {
        $model =& ClassRegistry::init($name);
      }

      if (empty($model) && $this->options['fileModel']) {
        $this->_error('FileUpload::getModel() - Model is not set or could not be found');
        return null;
      }
    }
		return $model;
	}
  
  /**
    * Adds error messages to the component
    *
    * @param string $text String of error message to save
    * @return void
    * @access protected
    */
  function _error($text){
    $message = __($text,true);
    $this->errors[] = $message;
    trigger_error($message,E_USER_WARNING);
  }
  
  /**
    * Returns an array of the uploaded file or false if there is not a file
    *
    * @return array|boolean Array of uploaded file, or false if no file uploaded
    * @access protected
    */
  function _uploadedFilesArray(){
    $retval = array();
    if($this->options['fileModel']){ //Model
      if(isset($this->data[$this->options['fileModel']][$this->options['fileVar']])) {
        $retval[][$this->options['fileVar']] = $this->data[$this->options['fileModel']][$this->options['fileVar']];
      }
      elseif(isset($this->data[$this->options['fileModel']][0][$this->options['fileVar']])){
        $retval = $this->data[$this->options['fileModel']];
      }
      else {
        $retval = false;
      }
    }
    else { // No model
      if(isset($this->params['form'][$this->options['fileVar']])){
        $retval[][$this->options['fileVar']] = $this->params['form'][$this->options['fileVar']];
      }
      elseif(isset($this->data[$this->options['fileVar']][0])){ //syntax for multiple files without a model is data[file][0]..data[file][1]..data[file][n]
        $retval = $this->data[$this->options['fileVar']];
      }
      elseif(isset($this->params['form'][$this->options['fileVar']][0])) {
        $this->_error("FileUpload: Multiple Files were detected without a model present, with the improper syntax. Use this naming scheme for your inputs: data[file][0]..data[file][1]..data[file][n]");
        $retval = false;
      }
      else {
        $retval = false;
      }
    }
            
    //cleanup array. unset any file in the array that wasn't actually uploaded.
    if($retval){
      foreach($retval as $key => $file){
        if(is_array($file) && isset($file[$this->options['fileVar']])){
          if(!empty($file[$this->options['fileVar']]) && !isset($file[$this->options['fileVar']]['error'])){
            $this->_error("FileUpload::_uploadedFilesArray() error.  Only a filename was detected, not the actual file.  Make sure you have enctype='multipart/form-data' in your form.  Please review documentation.");
          }
          if(isset($file[$this->options['fileVar']]['error']) && $file[$this->options['fileVar']]['error'] == UPLOAD_ERR_NO_FILE){
            unset($retval[$key]);
          }
        }
        elseif($this->options['fileModel']) {
          unset($retval[$key]);
        }
      }
    }
    
    //spit out an error if a file was detected but nothing is being returned by this method.
    if($this->uploadDetected && $retval === false){
      $this->_error("FileUpload: A file was detected, but was unable to be processed due to a misconfiguration of FileUpload. Current config -- fileModel:'{$this->options['fileModel']}' fileVar:'{$this->options['fileVar']}'");
    }
    
    return $retval;
  }
  
  /**
    * Searches through the $haystack for a $key.
    *
    * @param string $needle String of key to search for in $haystack
    * @param array $haystack Array of which to search for $needle
    * @return boolean true if given key is in an array
    * @access protected
    */
  function _multiArrayKeyExists($needle, $haystack) {
    if(is_array($haystack)){
      foreach ($haystack as $key=>$value) {
        if ($needle===$key && $value) {
          return true;
        }
        if (is_array($value)) {
          if($this->_multiArrayKeyExists($needle, $value)){
            return true;
          }
        }
      }
    }
    return false;
  }
}

?>
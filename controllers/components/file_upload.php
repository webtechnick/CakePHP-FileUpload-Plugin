<?php
/**
* FileUpload Component
*
* Manages uploaded files to be saved to the file system.
*
* @copyright    Copyright 2009, Webtechnick
* @link         http://www.webtechnick.com
* @author       Nick Baker
* @version      3.6.3
* @license      MIT
*/
class FileUploadComponent extends Object{
  /**
    * fileModel is the name of the model used if we want to 
    *  keep records of uploads in a database.
    * 
    * if you don't wish to use a database, simply set this to null
    *  $this->FileUpload->fileModel = null;
    *
    * @var mixed
    * @access public
    */
  var $fileModel = 'Upload';
  
  /**
    * uploadDir is the directory name in the webroot that you want
    * the uploaded files saved to.  default: files which means
    * webroot/files must exist and set to chmod 777
    *
    * @var string
    * @access public
    */
  var $uploadDir = 'files';
  
  /**
    * fileVar is the name of the key to look in for an uploaded file
    * For this to work you will need to use the
    * $form-input('file', array('type'=>'file)); 
    *
    * If you are NOT using a model the input must be just the name of the fileVar
    * input type='file' name='file'
    *
    * @var string
    * @access public
    */
  var $fileVar = 'file';
  
  /**
    * massSave is used if you'd like the plugin to handle associative records
    * along with just the Uploaded data.  By default this is turned off.
    * Turning this feature on will require you to have your model associations
    * set correctly in your Upload model.
    *
    * @var boolean
    * @access public
    */
  var $massSave = false;
  
  /**
    * allowedTypes is the allowed types of files that will be saved
    * to the filesystem.  You can change it at anytime without
    * $this->FileUpload->allowedTypes = array('text/plain',etc...);
    *
    * @var array
    * @access public
    */
  var $allowedTypes = array(
    'image/jpeg',
    'image/gif',
    'image/png',
    'image/pjpeg',
    'image/x-png'
  );
  
  /**
    * fields are the fields relating to the database columns
    *
    * @var array
    * @access public
    */
  var $fields = array('name'=>'name','type'=>'type','size'=>'size');
  
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
   * automatic determines if the process of all files will be called automatically upon detection.
   * if true: files are processed as soon as they come in
   * if false: when a file is ready hasFile is set to true
   * it is then up to the calling application to call processAllFiles()
   * whenever it wants. this allows params to be changed per uploaded file
   * (save every file in a different folder for instance)
   *
   * @contributer Elmer (http://bakery.cakephp.org/articles/view/file-upload-component-w-automagic-model-optional)
   * @var boolean
   * @access public
   */
  var $automatic = true; 
  
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
    * Definitions of errors that could occur during upload
    * 
    * @author Jon Langevin
    * @var array
    */
  var $upload_errors = array(
    UPLOAD_ERR_OK => 'There is no error, the file uploaded with success.',
    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.', //Introduced in PHP 4.3.10 and PHP 5.0.3.
    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.', //Introduced in PHP 5.1.0.
    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.' //Introduced in PHP 5.2.0.
  );
  
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
    * Initializes FileUploadComponent for use in the controller
    *
    * @param object $controller A reference to the instantiating controller object
    * @return void
    * @access public
    */
  function initialize(&$controller){
    $this->data = $controller->data;
    $this->params = $controller->params;
  }
  
  /**
    * Main execution method.  Handles file upload automatically upon detection and verification.
    *
    * @param object $controller A reference to the instantiating controller object
    * @return void
    * @access public
    */
  function startup(&$controller){
    $this->uploadDetected = ($this->_multiArrayKeyExists("tmp_name", $this->data) || $this->_multiArrayKeyExists("tmp_name",$this->params));
    $this->uploadedFiles = $this->_uploadedFilesArray();
    
    if($this->uploadDetected){
      $this->hasFile = true;
      if($this->automatic) { $this->processAllFiles(); }
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
    
    $up_dir = WWW_ROOT . $this->uploadDir;
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
    $name = $upload[$this->fileModel][$this->fields['name']];
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
    
    $up_dir = WWW_ROOT . $this->uploadDir;
    $target_path = $up_dir . DS . $this->currentFile['name'];
    $temp_path = substr($target_path, 0, strlen($target_path) - strlen($this->_ext())); //temp path without the ext
    //make sure the file doesn't already exist, if it does, add an itteration to it
		$i=1;
		while(file_exists($target_path)){
			$target_path = $temp_path . "-" . $i . $this->_ext();
			$i++;
		}
    
    //Ability to dynamically add other model fields added by Jon Langevin
    $save_data = $this->__prepareSaveData();
    
    if(move_uploaded_file($this->currentFile['tmp_name'], $target_path)){
      $this->finalFiles[] = basename($target_path);
      $this->finalFile = basename($target_path); //backported.  //finalFile is now depreciated
      $save_data[$this->fileModel][$this->fields['name']] = $this->finalFile;
      $save_data[$this->fileModel][$this->fields['type']] = $this->currentFile['type'];
      $save_data[$this->fileModel][$this->fields['size']] = $this->currentFile['size'];
      $model =& $this->getModel();
      if(!$model || $model->saveAll($save_data)){
        $this->success = true;
        if($model){
          $this->uploadIds[] = $model->id;
          $this->uploadId = $model->id; //backported. //uploadId is now depreciated.
          $model->create(); //get ready for the next one.
        }
      }
    }
    else{
      $this->_error('FileUpload::processFile() - Unable to save temp file to file system.');
    }
  }
  
  /** __prepareSaveData is used to help generate the array structure depending
    * that relys on $this->massSave to decide how to structure the save data for
    * the upload.
    *
    * @access private
    * @return array of prepared savedata.
    */
  function __prepareSaveData(){
    $retval = array();
    
    if($this->fileModel){
      if($this->massSave){
        $retval = $this->data;
        for($i=0;$i<count($this->uploadedFiles);$i++){
          unset($retval[$this->fileModel][$i]);
        } 
      }
      else {
        $retval = $this->data[$this->fileModel];
        for($i=0;$i<count($this->uploadedFiles);$i++){
          unset($retval[$i]);
        }
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
      if($this->_checkFile() && $this->_checkType()){
        $this->processFile();
      }
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
    if($this->fileModel){
      $this->currentFile = $file[$this->fileVar];
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
			$name = $this->fileModel;
		}
    
    if($name){
      if (PHP5) {
        $model = ClassRegistry::init($name);
      } else {
        $model =& ClassRegistry::init($name);
      }

      if (empty($model) && $this->fileModel) {
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
    * Checks if the uploaded type is allowed defined in the allowedTypes
    *
    * @return boolean if type is accepted
    * @access protected
    */
  function _checkType(){
    foreach($this->allowedTypes as $value){
      if(strtolower($this->currentFile['type']) == strtolower($value)){
        return true;
      }
    }
    $this->_error("FileUpload::_checkType() {$this->currentFile['type']} is not in the allowedTypes array.");
    return false;
  }
  
   /**
     * Checks if there is a file uploaded
     *
     * @return void
     * @access protected
     */
    function _checkFile(){
      if($this->uploadDetected && $this->currentFile){
        if($this->currentFile['error'] == UPLOAD_ERR_OK ) {
          return true;
        }
        else {
          $this->_error($this->upload_errors[$this->currentFile['error']]);
        }
      }        
      return false;
    } 
  
  /**
    * Returns the extension of the uploaded filename.
    *
    * @return string $extension A filename extension
    * @access protected
    */
  function _ext(){
    return strrchr($this->currentFile['name'],".");
  }
  
  /**
    * Returns an array of the uploaded file or false if there is not a file
    *
    * @return array|boolean Array of uploaded file, or false if no file uploaded
    * @access protected
    */
  function _uploadedFilesArray(){
    $retval = array();
    if($this->fileModel){ //Model
      if(isset($this->data[$this->fileModel][$this->fileVar])) {
        $retval[] = $this->data[$this->fileModel][$this->fileVar];
      }
      elseif(isset($this->data[$this->fileModel][0][$this->fileVar])){
        $retval = $this->data[$this->fileModel];
      }
      else {
        $retval = false;
      }
    }
    else { // No model
      if(isset($this->params['form'][$this->fileVar])){
        $retval[] = $this->params['form'][$this->fileVar];
      }
      elseif($this->data[$this->fileVar][0]){ //syntax for multiple files without a model is data[file][0]..data[file][1]..data[file][n]
        $retval = $this->data[$this->fileVar];
      }
      elseif(isset($this->params['form'][$this->fileVar][0])) {
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
        if(is_array($file) && isset($file[$this->fileVar])){
          if(!empty($file[$this->fileVar]) && !isset($file[$this->fileVar]['error'])){
            $this->_error("FileUpload::_uploadedFilesArray() error.  Only a filename was detected, not the actual file.  Make sure you have enctype='multipart/form-data' in your form.  Please review documentation.");
          }
          if(isset($file[$this->fileVar]['error']) && $file[$this->fileVar]['error'] == UPLOAD_ERR_NO_FILE){
            unset($retval[$key]);
          }
        }
        else {
          unset($retval[$key]);
        }
      }
    }
    
    //spit out an error if a file was detected but nothing is being returned by this method.
    if($this->uploadDetected && $retval === false){
      $this->_error("FileUpload: A file was detected, but was unable to be processed due to a misconfiguration of FileUpload. Current config -- fileModel:'{$this->fileModel}' fileVar:'{$this->fileVar}'");
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
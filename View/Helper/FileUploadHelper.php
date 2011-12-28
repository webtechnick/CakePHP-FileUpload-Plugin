<?php
/**
  * FileUPloadHelper is the helper to the component FileUploadComponent.
  * This helper REQUIRES the FileUploadComponent.
  *
  * @author: Nick Baker
  * @version: 6.1.1
  * @email: nick@webtechnick.com
  * @link: http://www.webtechnick.com/blogs/view/221/CakePHP_File_Upload_Plugin
  *
  * @example
  *      Show an already uploaded image
  *      $fileUpload->image('filename.jpg', array('width' => 250)); //resizes a thumbnail of 'filename.jpg' to 250
  *      $fileUpload->image('filename.jpg', array('width' => 250, 'uploadDir' => 'custom/dir')); //resizes a thumbnail of 'webroot/custom/dir/filename.jpg' to 250
  *
  *      Show the upload field form
  *      $fileUpload->input(); //builds the input form based on your FileUploadComponent defaults
  *           -or-
  *      $fileUpload->input(array('var' => 'fileVar', 'model' => 'Picture')); //customized input form.
  *      
  */
App::import('Config', 'FileUpload.file_upload_settings');
class FileUploadHelper extends AppHelper{
  var $helpers = array('Html', 'Form');
    
  /**
    * the name of the file passed in.
    */
  var $fileName = NULL;
  
  /**
    * Holds the FileUpload component
    */
  var $FileUpload = NULL;
  
  /**
    * Counts the number of inputs, for multiple fileUpload inputing.
    */
  var $inputCount = 0;
  
  /**
    * Default options for showImage
    *
    * - width: the width of the image to display (0 means no resizing (default))
    * - resizedDir: is the directory in which to save the resized files into (resized by default)
    * - imagePathOnly: will only return the requested image_path (false by default)
    * - autoResize: will resize the file automatically if given a valid width. (true by default)
    * - resizeThumbOnly: will only resize the image down -- not up past the original's size (true by default)
    */
  var $options = array(
    'width' => 0, //0 means no resizing
    'resizedDir' => 'resized', // make sure webroot/files/resized is chmod 777
    'imagePathOnly' => false, //if true, will only return the requested image_path
    'autoResize' => true, //if true, will resize the file automatically if given a valid width.
    'resizeThumbOnly' => true //if true, will only resize the image down -- not up past the original's size
  );
  
  /**
    * FileUpload Settings set in config/file_upload_settings.php
    */
  var $settings = array();
  
  /**
    * Constructor, initiallizes the FileUpload Component
    * and sets the default options.
    */
  function __construct(){
    $this->FileUploadSettings = new FileUploadSettings;
    
    //setup settings
    $this->settings = array_merge($this->FileUploadSettings->defaults, $this->options);
  }
  
  /**
    * Reset the helper to its initial state
    * @access public
    * @return void
    */
  function reset(){
    $this->fileName = null;
    $this->options = array(
      'width' => 0, 
      'resizedDir' => 'resized', 
      'imagePathOnly' => false, 
      'autoResize' => true, 
      'resizeThumbOnly' => true
    );
    
    //setup settings
    $this->settings = array_merge($this->FileUploadSettings->defaults, $this->options);
    unset($this->newImage);
  }
  
  /**
    * image takes a file_name or Upload.id and returns the HTML image
    *
    * @param String|Int $name takes a file_name or ID of uploaded file.
    * @param Array|Int $options takes an array of options passed to the image helper, or an integer representing the width of the image to display
    *         options: width = 100 (default), if width is set along with autoResize the uploaded image will be resized.
    * @access public
    * @return mixed html tag, url string, or false if unable to find image. 
    */
  function image($name, $options = array()){
    $this->fileName = $name;
    //options takes in a width as well
    if(is_int($options)){
      $width = $options;
      $options = array();
      $options['width'] = $width;
    }
    $this->options = array_merge($this->options, $options);
    $this->settings = array_merge($this->settings, $options);
      
    $img = false;
    if(is_string($name)){
      $img = $this->_getImageByName();
    }
    elseif(is_int($name)){
      $img = $this->_getImageById();
    }
    
    if($img){
      return $img;
    }
    
    $this->log("Unable to find $img");
    return false;
  }
  
  /** 
    * input takes an array of options and display the file browser html input
    * options.
    * @param Array $options of model and file options.  Defaults to default FileUpload component configuration
    * @return String HTML form input element configured for the FileUploadComponent
    * @access public
    */
  function input($options = array()){
    $options = array_merge(
      array('var' => $this->settings['fileVar'],'model' => $this->settings['fileModel']),
      $options
    );
    $configs = $options;
    if($configs['model']){
      unset($options['model'], $options['var']);
      
      return $this->Form->input("{$configs['model']}.".$this->inputCount++.".{$configs['var']}", array_merge(array('type'=>'file'), $options));
    }
    else {
      return "<input type='file' name='data[{$configs['var']}][".$this->inputCount++."]' />";
    }
  }
  
  /**
    * @access protected
    */
  function _getImageById(){
    App::import('Component', 'FileUpload.FileUpload');
    $this->FileUpload = new FileUploadComponent;
    
    $id = $this->fileName;
    $this->FileUpload->options['fileModel'] = $this->settings['fileModel'];
    $Model =& $this->FileUpload->getModel();
    $Model->recursive = -1;
    $upload = $Model->findById($id);
    if(!empty($upload)){
      $this->fileName = $upload[$this->settings['fileModel']][$this->settings['fields']['name']];
      return $this->_getImageByName();
    }
    else{
      return false;
    }
  }
  
  /**
    * _getFullPath returns the full path of the file name
    * @access protected
    * @return String full path of the file name
    */
  function _getFullPath(){
    if($this->_isOutsideSource()){
      return $this->fileName;
    }
    else {
      return WWW_ROOT . $this->_getUploadPath();
    }
  }
  
  /**
    * _getImagePath returns the image path of the file name
    * @access protected
    * @return String full path of the file name
    */
  function _getImagePath(){
    if($this->_isOutsideSource()){
      return $this->fileName;
    }
    else {
      return '/' . $this->_getUploadPath();
    }
  }
  
  /**
    * _getUploadPath returns the upload path of all files 
    * @access protected
    * @return String upload path of all files
    */
  function _getUploadPath(){
    return $this->settings['uploadDir'] . '/' . $this->fileName;
  }
  
  /**
    * _getExt returns the extension of the filename.
    * @access protected
    * @return String extension of filename
    */
  function _getExt(){
    return strrchr($this->fileName,".");
  }
  
  /**
    * Get the image by name and width.
    * if width is not specified return full image
    * if width is specified, see if width of image exists
    * if not, make it, save it, and return it.
    * @return String HTML of resized or full image.
    */
  function _getImageByName(){
    //only proceed if we actually have the file in question
    if(!$this->_isOutsideSource() && !file_exists($this->_getFullPath())) return false;
    //resize if we have resize on, a width, and if it doesn't already exist.
    if($this->options['autoResize'] && $this->options['width'] > 0 && !file_exists($this->_getResizeNameOrPath($this->_getFullPath()))){
      $this->_resizeImage();
    }
    return $this->_htmlImage();
  }
  
  /**
    * @return String of the resizedpath of a filename or path.
    * @access protected
    */
  function _getResizeNameOrPath($file_name_or_path){
    $file_name = basename($file_name_or_path);
    $path = substr($file_name_or_path, 0, strlen($file_name_or_path) - strlen($file_name));
    $temp_path = substr($file_name,0,strlen($file_name) - strlen($this->_getExt())) . "x" . $this->options['width'] . $this->_getExt();
    $full_path = (strlen($this->options['resizedDir']) > 0) ? $path . $this->options['resizedDir'] . '/' . $temp_path : $path . $temp_path;
    return $full_path;
  }
  
  /**
    * _resizeImage actually resizes the passed in image.
    * @access protected
    * @return null
    */
  function _resizeImage(){
    $this->newImage = new RResizeImage($this->_getFullPath());
    if($this->newImage->imgWidth > $this->options['width']){
      $this->newImage->resize_limitwh($this->options['width'], 0, $this->_getResizeNameOrPath($this->_getFullPath()));
    }
    else {
      //$this->autoResize = false;
    }
  }
  
  /**
    * _htmlImage returns the atual HTML of the resized/full image asked for
    * @access protected
    * @return String HTML image asked for
    */
  function _htmlImage(){
    if(!$this->_isOutsideSource() && $this->options['autoResize'] && $this->options['width'] > 0){
      if(isset($this->newImage) && $this->newImage->imgWidth && $this->newImage->imgWidth <= $this->options['width']){
        $image = $this->_getImagePath();
      }
      else {
        $image = $this->_getResizeNameOrPath($this->_getImagePath());
      }
    }
    else {
      $image = $this->_getImagePath();
    }
    
    $options = $this->options; //copy
    //unset the default options
    unset($options['resizedDir'], $options['uploadDir'], $options['imagePathOnly'], $options['autoResize'], $options['resizeThumbOnly']);
    //unset width only if we're not an outsourced image, we have resize turned on, or we don't have a width to begin with.
    if(!$this->_isOutsideSource() && ($this->options['resizeThumbOnly'] || !$options['width'])) unset($options['width']); 
    
    //return the impage path or image html
    if($this->options['imagePathOnly']){
      return $image;
    }
    else {
      return $this->Html->image($image, $options); 
    }
  }
  
  /**
    * _isOutsideSource searches the fileName string for :// to determine if the image source is inside or outside our server
    */
  function _isOutsideSource(){
    return !!strpos($this->fileName, '://');
  }
}




	/**
	 * Image Resizer. 
	 * @author : Harish Chauhan
	 * @copyright : Freeware
	 * About :This PHP script will resize the given image and can show on the fly or save as image file.
	 *
	 */
	//define("HAR_AUTO_NAME",1);	
	Class RResizeImage	{
		var $imgFile="";
		var $imgWidth=0;
		var $imgHeight=0;
		var $imgType="";
		var $imgAttr="";
		var $type=NULL;
		var $_img=NULL;
		var $_error="";
		
		/**
		 * Constructor
		 *
		 * @param [String $imgFile] Image File Name
		 * @return RESIZEIMAGE (Class Object)
		 */
		function __construct($imgFile=""){
			if (!function_exists("imagecreate")){
				$this->_error="Error: GD Library is not available.";
				return false;
			}

			$this->type=Array(1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF', 5 => 'PSD', 6 => 'BMP', 7 => 'TIFF', 8 => 'TIFF', 9 => 'JPC', 10 => 'JP2', 11 => 'JPX', 12 => 'JB2', 13 => 'SWC', 14 => 'IFF', 15 => 'WBMP', 16 => 'XBM');
			if(!empty($imgFile)){
				$this->setImage($imgFile);
      }
		}
    
		/**
		 * Error occured while resizing the image.
		 *
		 * @return String 
		 */
		function error(){
			return $this->_error;
		}
		
		/**
		 * Set image file name
		 *
		 * @param String $imgFile
		 * @return void
		 */
		function setImage($imgFile){
			$this->imgFile=$imgFile;
			return $this->_createImage();
		}
		
    /** 
		 * @return void
		 */
		function close(){
			return @imagedestroy($this->_img);
		}
    
		/**
		 * Resize a image to given width and height and keep it's current width and height ratio
		 * 
		 * @param Number $imgwidth
		 * @param Numnber $imgheight
		 * @param String $newfile
		 */
		function resize_limitwh($imgwidth,$imgheight,$newfile=NULL){
			$image_per = 100;
			list($width, $height, $type, $attr) = @getimagesize($this->imgFile);
			if($width > $imgwidth && $imgwidth > 0){
				$image_per = (double)(($imgwidth * 100) / $width);
      }

			if(floor(($height * $image_per)/100)>$imgheight && $imgheight > 0){
				$image_per = (double)(($imgheight * 100) / $height);
      }

			$this->resize_percentage($image_per,$newfile);
		}
		 
    /**
		 * Resize an image to given percentage.
		 *
		 * @param Number $percent
		 * @param String $newfile
		 * @return Boolean
		 */
		function resize_percentage($percent=100,$newfile=NULL)	{
			$newWidth=($this->imgWidth*$percent)/100;
			$newHeight=($this->imgHeight*$percent)/100;
			return $this->resize($newWidth,$newHeight,$newfile);
		}
		
    /**
		 * Resize an image to given X and Y percentage.
		 *
		 * @param Number $xpercent
		 * @param Number $ypercent
		 * @param String $newfile
		 * @return Boolean
		 */
		function resize_xypercentage($xpercent=100,$ypercent=100,$newfile=NULL)		{
			$newWidth=($this->imgWidth*$xpercent)/100;
			$newHeight=($this->imgHeight*$ypercent)/100;
			return $this->resize($newWidth,$newHeight,$newfile);
		}
		
		/**
		 * Resize an image to given width and height
		 *
		 * @param Number $width
		 * @param Number $height
		 * @param String $newfile
		 * @return Boolean
		 */
		function resize($width,$height,$newfile=NULL){
			if(empty($this->imgFile)){
				$this->_error="File name is not initialised.";
				return false;
			}
			if($this->imgWidth<=0 || $this->imgHeight<=0){
				$this->_error="Could not resize given image";
				return false;
			}
			if($width<=0)	$width=$this->imgWidth;
			if($height<=0) $height=$this->imgHeight;
				
			return $this->_resize($width,$height,$newfile);
		}
		
		/**
		 * Get the image attributes
		 * @access Private
		 * 		
		 */
		function _getImageInfo()
		{
			@list($this->imgWidth,$this->imgHeight,$type,$this->imgAttr)=@getimagesize($this->imgFile);
			$this->imgType=$this->type[$type];
		}
		
		/**
		 * Create the image resource 
		 * @access Private
		 * @return Boolean
		 */
		function _createImage(){
			$this->_getImageInfo();
			if($this->imgType=='GIF'){
				$this->_img=@imagecreatefromgif($this->imgFile);
			}
			elseif($this->imgType=='JPG'){
				$this->_img=@imagecreatefromjpeg($this->imgFile);
			}
			elseif($this->imgType=='PNG'){
				$this->_img=@imagecreatefrompng($this->imgFile);
			}
			
			if(!$this->_img || !@is_resource($this->_img)){
				$this->_error="Error loading ".$this->imgFile;
				return false;
			}
			return true;
		}
		
		/**
		 * Function is used to resize the image
		 * 
		 * @access Private
		 * @param Number $width
		 * @param Number $height
		 * @param String $newfile
		 * @return Boolean
		 */
		function _resize($width,$height,$newfile=NULL){
			if (!function_exists("imagecreate")){
				$this->_error="Error: GD Library is not available.";
				return false;
			}

			$newimg=@imagecreatetruecolor($width,$height);
			//imagecolortransparent( $newimg, imagecolorat( $newimg, 0, 0 ) );
			
			if($this->imgType=='GIF' || $this->imgType=='PNG')	{
				/** Code to keep transparency of image **/
				$colorcount = imagecolorstotal($this->_img);
				if ($colorcount == 0) $colorcount = 256;
				imagetruecolortopalette($newimg,true,$colorcount);
				imagepalettecopy($newimg,$this->_img);
				$transparentcolor = imagecolortransparent($this->_img);
				imagefill($newimg,0,0,$transparentcolor);
				imagecolortransparent($newimg,$transparentcolor); 
			}

			@imagecopyresampled ( $newimg, $this->_img, 0,0,0,0, $width, $height, $this->imgWidth,$this->imgHeight);
			
			if($newfile===1)	{
				if(@preg_match("/\..*+$/",@basename($this->imgFile),$matches)){
			   		$newfile=@substr_replace($this->imgFile,"_har",-@strlen($matches[0]),0);
        }
			}
			elseif(!empty($newfile)){
				if(!@preg_match("/\..*+$/",@basename($newfile))){
					if(@preg_match("/\..*+$/",@basename($this->imgFile),$matches)){
					   $newfile=$newfile.$matches[0];
          }
				}
			}

			if($this->imgType=='GIF'){
				if(!empty($newfile)){
          @imagegif($newimg,$newfile);
        }
				else {
					@header("Content-type: image/gif");
					@imagegif($newimg);
				}
			}
			elseif($this->imgType=='JPG'){
				if(!empty($newfile)){
					@imagejpeg($newimg,$newfile);
        }
				else	{
					@header("Content-type: image/jpeg");
					@imagejpeg($newimg);
				}
			}
			elseif($this->imgType=='PNG'){
				if(!empty($newfile)){
					@imagepng($newimg,$newfile);
        }
				else{
					@header("Content-type: image/png");
					@imagepng($newimg);
				}
			}
			@imagedestroy($newimg);
		}
	}
?>
<?php
/*************************************************
  * FileUPloadHelper is the helper to the component FileUploadComponent.
  * This helper REQUIRES the FileUploadComponent.
  *
  * @author: Nick Baker
  * @version: 3.0
  * @email: nick@webtechnick.com
  * @link: http://projects.webtechnick.com/file_upload
  * @svn: svn co https://svn.xp-dev.com/svn/nurvzy_file_upload
  *
  *  USAGE:
  *      Show an already uploaded image
  *      $fileUpload->image('filename.jpg', array('width => 250')); //resizes a thumbnail of 'filename.jpg' to 250
  *
  *      Show the upload field form
  *      $fileUpload->input(); //builds the input form based on your FileUploadComponent defaults
  *           -or-
  *      $fileUpload->input(array('var' => 'fileVar', 'model' => 'Picture')); //customized input form.
  *      
  */
class FileUploadHelper extends AppHelper{
  var $helpers = array('Html', 'Form');
  
  /************************************************
    * the name of the file passed in.
    */
  var $fileName = NULL;
  
  /************************************************
    * Holds the FileUpload component
    */
  var $FileUpload = NULL;
  
  /************************************************
    * autoResize will resize the file for quicker thumbnail displays
    */
  var $autoResize = true;
  
  /************************************************
    * resizeThumbOnly means passing in widths larger than the origial image
    * will not use the GD Library to resize nor will it ask the browser to resize.
    * default true
    */
  var $resizeThumbOnly = true;
  
  /************************************************
    * Default options for showImage
    */
  var $options = array(
    'width' => 0, //0 means no resizing
    'resizedDir' => 'resized', // make sure webroot/files/resized is chmod 777
    'image_path_only' => false
  );
  
  /************************************************
    * Constructor, initiallizes the FileUpload Component
    * and sets the default options.
    */
  function __construct(){
    App::import('Component', 'FileUpload.FileUpload');
    $this->FileUpload = new FileUploadComponent;
    $this->options['uploadDir'] = $this->FileUpload->uploadDir;
  }
  
  /************************************************************
    * image takes a file_name or Upload.id and returns the HTML image
    *
    * @param String|Int $name takes a file_name or ID of uploaded file.
    * @param Array|Int $options takes an array of options passed to the image helper, or an integer representing the width of the image to display
    *         options: width = 100 (default), if width is set along with autoResize the uploaded image will be resized.
    * @access public
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
  
  /************************************************** 
    * input takes an array of options and display the file browser html input
    * options.
    * @param Array $options of model and file options.  Defaults to default FileUpload component configuration
    * @return String HTML form input element configured for the FileUploadComponent
    * @access public
    */
  function input($options = array()){
    $options = array_merge(
      array('var' => $this->FileUpload->fileVar,'model' => $this->FileUpload->fileModel),
      $options
    );
    $configs = $options;
    if($configs['model']){
      unset($options['model'], $options['var']);
      return $this->Form->input("{$configs['model']}.{$configs['var']}", array_merge(array('type'=>'file'), $options));
    }
    else {
      return "<input type='file' name='{$configs['var']}' />";
    }
  }
  
  /**************************************************
    * @access protected
    */
  function _getImageById(){
    $id = $this->fileName;
    $Model =& $this->FileUpload->getModel();
    $Model->recursive = -1;
    $upload = $Model->findById($id);
    if(!empty($upload)){
      $this->fileName = $upload[$this->FileUpload->fileModel][$this->FileUpload->fields['name']];
      return $this->_getImageByName();
    }
    else{
      return false;
    }
  }
  
  /**************************************************
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
  
  /**************************************************
    * _getImagePath returns the image path of the file name
    * @access protected
    * @return String full path of the file name
    */
  function _getImagePath(){
    if($this->_isOutsideSource()){
      return $this->fileName;
    }
    else {
      return DS . $this->_getUploadPath();
    }
  }
  
  /**************************************************
    * _getUploadPath returns the upload path of all files 
    * @access protected
    * @return String upload path of all files
    */
  function _getUploadPath(){
    return $this->options['uploadDir'] . DS . $this->fileName;
  }
  
  /**************************************************
    * _getExt returns the extension of the filename.
    * @access protected
    * @return String extension of filename
    */
  function _getExt(){
    return strrchr($this->fileName,".");
  }
  
  /************************************************
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
    if($this->autoResize && $this->options['width'] > 0 && !file_exists($this->_getResizeNameOrPath($this->_getFullPath()))){
       $this->_resizeImage();
    }
    return $this->_htmlImage();
  }
  
  /**************************************************
    * @return String of the resizedpath of a filename or path.
    * @access protected
    */
  function _getResizeNameOrPath($file_name_or_path){
    $file_name = basename($file_name_or_path);
    $path = substr($file_name_or_path, 0, strlen($file_name_or_path) - strlen($file_name));
    $temp_path = substr($file_name,0,strlen($file_name) - strlen($this->_getExt())) . "x" . $this->options['width'] . $this->_getExt();
    $full_path = (strlen($this->options['resizedDir']) > 0) ? $path . $this->options['resizedDir'] . DS . $temp_path : $path . $temp_path;
    return $full_path;
  }
  
  /**************************************************
    * _resizeImage actually resizes the passed in image.
    * @access protected
    * @return null
    */
  function _resizeImage(){
    $new_image = new RResizeImage($this->_getFullPath());
    if($new_image->imgWidth > $this->options['width']){
      $new_image->resize_limitwh($this->options['width'], 0, $this->_getResizeNameOrPath($this->_getFullPath()));
    }
    else {
      $this->autoResize = false;
    }
  }
  
  /**************************************************
    * _htmlImage returns the atual HTML of the resized/full image asked for
    * @access protected
    * @return String HTML image asked for
    */
  function _htmlImage(){
    $image = (!$this->_isOutsideSource() && $this->autoResize && $this->options['width'] > 0) ? $this->_getResizeNameOrPath($this->_getImagePath()) : $this->_getImagePath();
    $options = $this->options; //copy
    //unset the default options
    unset($options['resizedDir']);
    unset($options['uploadDir']);
    if(!$this->_isOutsideSource() && ($this->resizeThumbOnly || !$options['width'])) unset($options['width']); 
    
    if($this->options['image_path_only']){
      return $image;
    }
    else {
      return $this->Html->image($image, $options); 
    }
  }
  
  /**************************************************
    * _isOutsideSource searches the fileName string for :// to determine if the image source is inside or outside our server
    */
  function _isOutsideSource(){
    return !!strpos($this->fileName, '://');
  }
}




	/************************************************************
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
		
		/************************************************************
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
    
		/************************************************************
		 * Error occured while resizing the image.
		 *
		 * @return String 
		 */
		function error(){
			return $this->_error;
		}
		
		/************************************************************
		 * Set image file name
		 *
		 * @param String $imgFile
		 * @return void
		 */
		function setImage($imgFile){
			$this->imgFile=$imgFile;
			return $this->_createImage();
		}
		
    /************************************************************ 
		 * @return void
		 */
		function close(){
			return @imagedestroy($this->_img);
		}
    
		/************************************************************
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
		 
    /************************************************************
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
		
    /************************************************************
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
		
		/************************************************************
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
		
		/************************************************************
		 * Get the image attributes
		 * @access Private
		 * 		
		 */
		function _getImageInfo()
		{
			@list($this->imgWidth,$this->imgHeight,$type,$this->imgAttr)=@getimagesize($this->imgFile);
			$this->imgType=$this->type[$type];
		}
		
		/************************************************************
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
		
		/************************************************************
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
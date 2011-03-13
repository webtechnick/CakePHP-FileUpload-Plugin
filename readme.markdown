# File Upload Plugin
* AUTHOR: Nick Baker
* VERSION: 6.1.1
* EMAIL: nick@webtechnick.com
* BLOG ARTICLE: <http://www.webtechnick.com/blogs/view/221/CakePHP_File_Upload_Plugin>

# INSTALL

clone into your `app/plugins/file_upload` directory
	
	git clone git://github.com/webtechnick/CakePHP-FileUpload-Plugin.git app/plugins/file_upload


# CHANGELOG:
* 6.1.1: Fixed a bug that would not display an image if the source image is the same width as the resize image requested. 
* 6.1.0: Allow users to change the uploadDir outside of WEB_ROOT by changing setting forceWebroot to false in the configuration. Default is still webroot/files.  Updated typos in the README.txt
* 6.0.0: Change the way file uploads types are checked.  Now checking extension along with filetypes. *Please read migration guide.  migration_guide_5_0_x_to_6_0_x.txt*
* 5.0.1: Fixed a bug that would continue a file upload if the fileName returned false after a filename callback.
* 5.0.0: Major release tag
* 4.4.0: Added new fileName maniupluation callbacks and settings.
* 4.3.0: Added a new 'maxFileSize' validation key.
* 4.2.0: Added a new 'required' key in Behavior settings that would produce a validation error if a file wasn't uploaded.
* 4.1.2: Fixed a regression,  passing in custom settings to the helper now changes those settings.
* 4.1.1: Bug fix displaying correct image path for Windows Servers.
* 4.1.0: Added validation errors for the behavior.  If an error is accurded durring an upload a validation error is thrown and presented to the user.
* 4.0.4: Bug fix, Undefined index notice within same controller on different form without an upload at all.
* 4.0.3: Bug fix, using wrong option in removeFile method.
* 4.0.2: Bug fix, uploading non-model files now returns proper array of files to be uploaded.
* 4.0.1: Bug fix, setting false values into the global settings now works again, errors in uploader translate to component errors.
* 4.0: Massive update, refactoring, new behavior, new configuration file.
* 3.6.3 Bug fix; assigning multiple columns to upload model key, doesn't test to make sure it's a file (regression fixed).
* 3.6.2 Bug fixes, multiple fileupload issue with finalFiles
* 3.6.1 Bug fixes (for non model users)
* 3.6: Added massSave associative array save support.
* 3.5: Added multi file support. (API changes: $uploadId now depreciated, use $uploadIds[0] instead.  $finalFile now depreciated, use $finalFiles[0] instead.)
* 3.0: Converted Component and Helper into a plugin for easy management between projects
* 2.0.1: Bug Fixes
* 2.0: Release of FileUploadHelper
* 1.7: Added detailed errors to FileUploadComponent
* 1.6: Bug Fixes
* 1.5: Bug Fixes
* 1.4: Added toggle to allow for auto processFile or not. 
* 1.3: Bug Fixes
* 1.2: Bug Fixes
* 1.1: Converted to cakePHP naming conventions and standards
* 1.0: Initial Release


# SETUP AND BASIC CONFIGURATIONS
There are two ways to setup the this plugin.  Number one, you can use the Behavior + Helper method.
by attaching the FileUpload.FileUpload behavior to a model of your choice any file uploaded while saving that
model will move the file to its specified area (webroot/files).  All the file uploading will happen for you
automatically, including multiple file uploads and associations.


# BEHAVIOR CONFIGURATION (RECOMMENDED)
The behavior configuration is the recommened way to handle file uploads. Your table will need to have three columns in it (`name`, `type`, and `size`).  Then handling uploads is as simple as attaching the behavior to your model.

Review `file_upload/config/sql/upload.php` for a schema file to work into your database

## Model Setup
Simply attach the FileUpload.FileUpload behavior to the model of your choice.

		<?php
		class Upload extends AppModel {
			var $name = 'Upload';
			var $actsAs = array('FileUpload.FileUpload');
		}
		?>

To set options in to your behavior like change the upload directory or the fileVar you'd like to use
for automatic file uploading simply pass them into your attachment like so:

		<?php
		class Upload extends AppModel {
			var $name = 'Upload';
			var $actsAs = array(
						'FileUpload.FileUpload' => array(
							'uploadDir' => 'files',
							'forceWebroot' => true //if false, files will be upload to the exact path of uploadDir
							'fields' => array('name' => 'file_name', 'type' => 'file_type', 'size' => 'file_size'),
							'allowedTypes' => array('pdf' => array('application/pdf')),
							'required' => false, //default is false, if true a validation error would occur if a file wsan't uploaded.
							'maxFileSize' => '10000', //bytes OR false to turn off maxFileSize (default false)
							'unique' => false //filenames will overwrite existing files of the same name. (default true)
							'fileNameFunction' => 'sha1' //execute the Sha1 function on a filename before saving it (default false)
						)
					);
		}
		?>

*NOTE:* Please review the `file_upload/config/file_upload_settings.php` for details on each setting.


## View Setup

Creating a view is actually quite simple.  You use an input called `file` and the Behavior will take care of the rest.
The important thing to remember is to use `'type' => 'file'` when creating the form.

Here is a trivial example:

		<?php
			echo $form->create('Upload', array('type' => 'file'));
			echo $form->input('file', array('type' => 'file'));
			echo $form->end();
		?>

Now with your upload model set, you'll be able to upload files and save to your database even through associations in other models.
Example:

Assuming an Application->hasMany->Uploads you could do the following:

		<?php
			echo $form->create('Application', array('type' => 'file'));
			echo $form->input('Application.name');
			echo $form->input('Upload.0.file', array('type' => 'file'));
			echo $form->input('Upload.1.file', array('type' => 'file'));
			echo $form->end('Save Application and Two Uploads');
		?>

The Behavior method is by far the easiest and most flexible way to get up and rolling with file uploads.

# VIEWING AN UPLOADED PHOTO EXAMPLES
To View the photo variable you might type something like

		$this->Html->image("/files/$photo");

Example with FileUploadHelper:

		$this->FileUpload->image($photo);


# COMPONENT CONFIGURATION (NOT RECOMMENDED)
The second options is to use the Component + Helper method.

*NOTE: This not the recommended way.  I do not recommend using the comopnent unless you do *not* require a model.*

Including with this plugin is another method that requires a component. The advantage of using a component is a model is not required for file uploading.  If you do not need a database, and you simply want to upload data to your server quickly and easily simply skip to the WITHOUT MODEL CONFIGURATION section of this readme.

## Controller Setup
You'll need to add the `FileUpload.FileUpload` in both the components and helpers array

		<?php
		var $helpers = array('Html', 'Form', 'FileUpload.FileUpload');
		var $components = array('FileUpload.FileUpload');
		?>

Upon submitting a file the FileUpload Component will automatically search for your uploaded file, verify its of the proper type set by `$this->FileUpload->allowedTypes():`

		<?php 
		function beforeFilter(){
			parent::beforeFilter();
			/* defaults to:
			'jpg' => array('image/jpeg', 'image/pjpeg'),
			'jpeg' => array('image/jpeg', 'image/pjpeg'), 
			'gif' => array('image/gif'),
			'png' => array('image/png','image/x-png'),*/
			
			$this->FileUpload->allowedTypes(array(
				'jpg' => array('image/jpeg','image/pjpeg'), 
				'txt', 
				'gif', 
				'pdf' => array('application/pdf')
			)); 
		}
		?>

Then it will attempt to copy the file to your uploads directory set by `$this->FileUpload->uploadDir:`
		<?php 
		function beforeFilter(){
			parent::beforeFilter();
			//defaults to 'files', will be webroot/files, make sure webroot/files exists and is chmod 777
			$this->FileUpload->uploadDir('files'); 
		}
		?>


## Model Setup With Component
You can use this Component with or without a model. It defaults to use the Upload model:

		<?php
		//app/models/upload.php 
		class Upload extends AppModel{
			var $name = 'Upload';
		}
		?>

### SQL EXAMPLE IF YOUR USING A MODEL
If you're using a Model, you'll need to have at least 3 fields to hold the uploaded data (`name`, `type`, `size`)
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

		cake schema create -path plugins/file_upload/config/sql -name upload

Default fields are name, type, and size; but you can change that at anytime using the `$this->FileUpload->fields = array();`

		<?php 
		function beforeFilter(){
			parent::beforeFilter();
			//fill with associated array of name, type, size to the corresponding column name
			$this->FileUpload->fields(array('name'=> 'name', 'type' => 'type', 'size' => 'size'));
		}
		?>

### VIEW WITH MODEL
Example view WITH Model WITH Helper:

		<?php echo $fileUpload->input(); ?>

#### Upload Multiple Files
The new helper will do all the hard work for you, you can just output input multiple times to allow for more than one file to be uploaded at a time.
		
		<?php echo $fileUpload->input(); ?>
		<?php echo $fileUpload->input(); ?>
		<?php echo $fileUpload->input(); ?>
		
#### Example View WITH Model WITHOUT Helper:
		<?php echo $form->input('file', array('type'=>'file')); ?>

Uploading Multiple Files

		<?php echo $form->input('Upload.0.file', array('type'=>'file')); ?>
		<?php echo $form->input('Upload.1.file', array('type'=>'file')); ?>
		<?php echo $form->input('Upload.3.file', array('type'=>'file')); ?>




### WITHOUT MODEL CONFIGURATION

If you wish to *NOT* use a model simply set `$this->FileUpload->fileModel(null);` in a beforeFilter.
		<?php 
			//in a controller
			function beforeFilter(){
				parent::beforeFilter();
				$this->FileUpload->fileModel(null);  //Upload by default.
			}
		?>

#### VIEW WITHOUT MODEL

	<input type="file" name="file" />

OR
	
	<?= $fileUpload->input(array('var' => 'file', 'model' => false)); ?>

#### Multiple File Uploading
The helper will do all the work for you, just output input multiple times and the rest will be done for you.
		<?php echo $fileUpload->input(array('var' => 'file', 'model' => false)); ?>
		<?php echo $fileUpload->input(array('var' => 'file', 'model' => false)); ?>
		<?php echo $fileUpload->input(array('var' => 'file', 'model' => false)); ?>

#### Without Helper example:
		<input type="file" name="data[file][0]" />
		<input type="file" name="data[file][1]" />
		<input type="file" name="data[file][2]" />



## CONTROLLER EXAMPLES
If a fileModel is given, it will attempt to save the record of the uploaded file to the database for later use. Upon success the FileComponent sets `$this->FileUpload->success` to TRUE; You can use this variable to test in your controller like so:

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

At any time you can remove a file by using the `$this->FileUpload->removeFile($name);` function. An example of that being used might be in a controller:
		<?php 
		class UploadsController extends AppController {
		
			var $name = 'Uploads';
			var $helpers = array('Html', 'Form', 'FileUpload.FileUpload');
			var $components = array('FileUpload.FileUpload');
			
			function admin_delete($id = null) {
				$upload = $this->Upload->findById($id);
				if($this->FileUpload->removeFile($upload['Upload']['name'])){
					if($this->Upload->delete($id)){
						$this->Session->setFlash('Upload deleted');
						$this->redirect(array('action'=>'index'));
					}
				}
			}
		}
		?>
<?php
class uploadSchema extends CakeSchema {
  var $name = 'upload';
  
  function before($event = array()) {
		return true;
	}

	function after($event = array()) {
	}  
  
  var $uploads = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 100),
		'type' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 100),
		'size' => array('type' => 'integer', 'null' => false, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => false, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1))
	);

}
?>
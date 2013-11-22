<?php
 /**
 * AUBME
 *
 * @version 0.1 Beta
 * @author Andy Abi Haidar
 * @license Open Source
 *
 * Note: make sure that in your php.ini file:
 * 		extension=php_openssl.dll is uncommented
 *		allow_url_fopen = On
 **/

 /** 
 * Defining an autoload function
 **/

 set_time_limit(0);
 ini_set("memory_limit","1200M");

 function __autoload($className) {
 	require_once('inc/' . $className . '_class.php');
 }

 abstract class ExtractAUBMeBlueprint {
 	public $finalLinks = array();
 	public $finalScheduleLink;

 	abstract protected function loadLink($link);
 }

 class ExtractAUBMe extends ExtractAUBMeBlueprint {
 	public function __construct($link) { 
 		$this -> functions = new Functions();
		$this -> finalScheduleLink = $this -> functions -> popFinalURLDelimeter($link, '/') . "/";
		$this -> loadLink($link);
 	}

 	protected function loadLink($link) {
 		$schedulePage = loadDOM::loadDocuments($link, true);
		
 		$links = $schedulePage -> find('table', 0) -> find('p', 0) -> find('a');
 		foreach($links as $link) {
 			$this -> finalLinks[] = $this -> finalScheduleLink . $link -> href;
 		}
		
 		$foobar = new ExtractSchedule($this -> finalLinks, 'spring');
 	}
 }


 try {
 	$foo = new ExtractAUBMe("https://www-banner.aub.edu.lb/catalog/schedule_header.html");
 } catch (Exception $e) {
 	echo $e -> getMessage();
 }

 ?>
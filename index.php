<?php
/**
* AUBME Course Extraction
*
* @version 1.0
* @author Andy Abi Haidar
* @license Open Source
*
* Note: make sure that in your php.ini file:
* 	extension=php_openssl.dll is uncommented
*   extension=php_tidy.dll is uncommented
*	allow_url_fopen = On
**/

// Setting time limit to unlimited since it will definitely take some time to extract the courses
set_time_limit(0);

// Setting the memory limit to 1.2GB, don't think you'll need more than that.
ini_set("memory_limit","1200M");


/** 
* Defining an autoload function
**/
function __autoload($className) {
	require_once('inc/' . $className . '_class.php');
}

abstract class ExtractAUBMeBlueprint {
	public $finalLinks = array();
	public $finalScheduleLink;

	abstract protected function loadLink($link);
}

/**
* ExtractAUBMe class that will extract the courses from the specified link
**/
class ExtractAUBMe extends ExtractAUBMeBlueprint {

	/**
	* Constructor function that will set up all the necessary parameters and then load the links
	* @param string $link URL to the AUB Dynamic Course Schedule page
	**/
	public function __construct($link) { 

		/**
		* @var object $functions 	new instance of the Functions library
		**/
		$this -> functions = new Functions();

		/**
		* @var string $finalScheduleLink 	URL to the Dynamic Course Schedule page without the last parameter so that we can set a proper link to each schedule page
		**/
		$this -> finalScheduleLink = $this -> functions -> popFinalURLDelimeter($link, '/') . "/";

		$this -> loadLink($link);
	}

	/**
	* Function that will load the page and then extract the schedule of each alphabetical page alone
	* @param string $link URL to the AUB Dynamic Course Schedule page
	**/
	protected function loadLink($link) {
		/**
		* @var object $schedulePage 	object containing the HTML in Simple HTML DOM Parser format of the DCS page
		**/
		$schedulePage = loadDOM::loadDocuments($link, true);
		
		/**
		* @var array $links 	array containing all the alphabetical links
		**/
		$links = $schedulePage -> find('table', 0) -> find('p', 0) -> find('a');

		foreach($links as $link) {

			/**
			* @var array $finalLinks	array containing all the URLs of the alphabetical links
			**/
			$this -> finalLinks[] = $this -> finalScheduleLink . $link -> href;
		}
		
		// Extracting the courses
		$foobar = new ExtractSchedule($this -> finalLinks, 'spring');
	}
}


try {
	// Using the proper link to the DCS page
	$foo = new ExtractAUBMe("https://www-banner.aub.edu.lb/catalog/schedule_header.html");

} catch (Exception $e) {

	echo $e -> getMessage();

}

?>
<?php

class loadDOM {
	protected static $HTMLParser = array();

	/**
	* Function that checks if the document is valid
	* @param string $file path to the html file that is going to be loaded via DOM
	**/
	private static function checkDocument($file) {
		$extension_ar = explode('.', $file);
		$extension = end($extension_ar);
		$accepted_extensions = array('htm', 'html');

		if(!$file || !in_array($extension, $accepted_extensions)) {
			throw new Exception("Invalid file: {$file}, please use a valid file.");
		}
	}

	/**
	* Function that loads the documents
	* @param string/array $files  string/array that contains the files in question
	* @param bool         $return whether or not to return the values
	**/
	public static function loadDocuments($files, $return = FALSE) {
		/**
		* Including the Simple HTML DOM Parser 
		**/
		if(!file_exists('inc/simple_html_dom_class.php')) {
			throw new Exception("Simple HTML DOM Parser does not exist.");
		}

		include_once('inc/simple_html_dom_class.php');


		/**
		* Checking if the file exists and is valid
		**/
		self::$HTMLParser = array();
		if(is_array($files)) {
			foreach($files as $file){
				self::checkDocument($file);
				self::$HTMLParser[] = file_get_html($file);
			}
		}

		else {
			self::checkDocument($files);
			self::$HTMLParser = file_get_html($files);
		}


		if($return) {
			return self::$HTMLParser;
		}
	}
}

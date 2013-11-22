<?php
/**
* Class containing all the functions that might be needed
**/
class Functions {

	/** 
	* Function that cleans out a certain/string array and removes special characters
	* @param  string|array $string string or array to clean
	* @param  bool         $space  whether or not to clean spacings as well (true means to clean)
	* @return string               returns the string without any special characters
	**/
	public function alphanumeric_clean($string, $space = true) {
		if(is_array($string)) foreach($string as &$string_alone) $string_alone = $this -> alphanumeric_clean($string_alone, $space);

		else {
			$string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
			if($space) $string = str_replace(' ', '', $string);
		}

		return $string;
	}

	/**
	* Function that returns everything before a delimeter in a URL
	* @param  string $link 		URL Link
	* @param  string $delimeter	Delimeter
	* @return string            URL without the last delimeter
	**/
	public function popFinalURLDelimeter($link, $delimeter) {
		$linkArray = explode($delimeter, $link);
		$finalLink = array_pop($linkArray);
		return implode($delimeter, $linkArray);
	}
}

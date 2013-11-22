<?php
abstract class ExtractScheduleBlueprint extends Config {
	protected $HTMLParsed, $dbh;
	public $properHeadings;

	abstract protected function getHeadingsRow($HTMLParsedArray);
	abstract protected function parseValues($HTMLParsedArray, $properHeadings, $semester);
	abstract protected function startScheduleTable($headings);
	abstract protected function stopScheduleTable();
}

/**
* Extract Schedule class that will handle all the extraction of the schedules
**/

class ExtractSchedule extends ExtractScheduleBlueprint {

	/**
	* @param string|array $file 	string or array that contains the schedule files
	* @param string       $semester fall/spring/summer 
	**/
	public function __construct($file = FALSE, $semester = false) {

		/**
		* @var object $HTMLParsed 	object containing the HTML parsing of the course page
		**/
		$this -> HTMLParsed = loadDOM::loadDocuments($file, true);

		$this -> getHeadingsRow($this -> HTMLParsed);
		$this -> parseValues($this -> HTMLParsed, $this -> properHeadings, $semester);
	}


	/**
	* Function that will get the proper indeces of each heading and then bind the values to a final array
	* @param object $HTMLParsedArray 	object containing the HTML code of the page
	**/
	protected function getHeadingsRow($HTMLParsedArray) {
		// For the headings, only the first array is required since they are all the same
		if(is_array($HTMLParsedArray)) $HTMLParsed = $HTMLParsedArray[0];
		else $HTMLParsed = $HTMLParsedArray;

		// Making sure it's a proper HTML DOM object
		if(method_exists($HTMLParsed, 'find') && is_object($HTMLParsed)) {

			/** 
			* The following will extract the headings and find their index
			* Note that this is relative to the structure of the course table on AUB's website
			* Any change in their structure will definitely require a change in the code
			**/

			/**
			* @var array $tds 	array containing all the header TDs
			**/
			$tds = $HTMLParsed -> find('table', 1) -> find('tr', 1) -> find('td');

			/**
			* @var array $neccessaryInformation 	array containing all the table columns that interests us (formatted to match the format of the AUB page)
			**/
			$necessaryInformation = array('C R N' => '',
										  'Subject' => '',
										  'Code' => '',
										  'BEGIN TIME 1' => '',
										  'END TIME  1' => '',
										  'BEGIN TIME 2' => '',
										  'END TIME  2' => '',
										  'S' => '',
										  'C' => '',
										  'H' => '',
										  'E' => '',
										  'D' => '',
										  'U' => '',
										  'S1' => '',
										  'C1' => '',
										  'H1' => '',
										  'E1' => '',
										  'D1' => '',
										  'U1' => '',
										  'Instructor F.Name' => '',
										  'Instructor Surame' => '');

			/**
			* @var int $i will be used to find the index of each necessary header
			**/
			$i = 1;

			foreach($tds as $td) {
				// Eliminating the useless HTML tags
				$td_ = trim($td -> plaintext);
				$td_ = str_replace("<br> ", "", $td_);

				// Foreach loop that will bind each index to its proper key
				foreach($necessaryInformation as $tdTitle => &$index) {
					if($td_ == $tdTitle) {

						// This check will allow me to store the indexes for the S1/C1... which are the lab and recitation times
						if(strlen($index) > 0) {
							$necessaryInformation[$tdTitle . '1'] = $i;
						}

						else $index = $i;
					}
				}	

				$i++;
			}

			/**
			* @var array $properInformation 	array containing the properly formatted columns
			**/
			$properInformation = array('CRN', 'Subject', 'Code', 'ClassStartTime', 'ClassEndTime', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'RecLabStartTime', 'RecLabEndTime', 'MondayRC', 'TuesdayRC', 'WednesdayRC', 'ThursdayRC', 'FridayRC', 'SaturdayRC', "InstructorsFirstName", "InstructorsLastName");

			/**
			* @var array $properHeadings 	array containing the indeces and their properly formatted column headers
			**/
			$this -> properHeadings = array_combine($properInformation, array_values($necessaryInformation));		
		}
	}

	/**
	* Function that will echo out the proper elements to start a table along with the headers extracted above
	* @param array $headings 	array containing all the headers that were extracted
	**/
	protected function startScheduleTable($headings) {
		echo "<table border='1'>";
		echo "<tr>";
		foreach($headings as $heading => $index) {
			echo "<th>{$heading}</th>";
		}
		echo "</tr>";
	}

	/**
	* Function that will close the table
	**/
	protected function stopScheduleTable() {
		echo "</table>";
	}

	/**
	* Function that will parse out all the courses into a proper table
	* @param array  $HTMLParsedArray 	array containing all the objects of each page
	* @param array  $properHeadings		array containing all the extracted headers
	* @param string $semester 			string containing which semester the information is going to be taken from (summer/fall/spring)
	**/
	protected function parseValues($HTMLParsedArray, $properHeadings, $semester) {

		// Starting the table
		$this -> startScheduleTable($properHeadings);

		// Since there are many pages, we iterate through each one
		foreach($HTMLParsedArray as $HTMLParsed) {

			// Making sure it's an object
			if(!is_object($HTMLParsed)) continue;

			// Selecting the proper table (since there are two in the DCS page) then getting its HTML
			$table_one = $HTMLParsed -> find('table', 1);
			$newParse =  str_get_html($table_one);

			/**
			* The following will use the PHP 5 bundled library called Tidy that will tidy up the code on each page since AUB does not know how to properly close their <tr> tags
			**/
			$config = array(
	           'indent'         => true,
	           'output-xhtml'   => true,
	           'wrap'           => 200);

			$tidy = new tidy;
			$tidy->parseString($newParse, $config, 'utf8');
			$tidy->cleanRepair();

			/**
			* @var string $newParse		variable containing the final HTML of each page
			**/
			$newParse = tidy_get_output($tidy);
			$newParse = str_get_html($newParse);

			// Getting all the rows
			$trs = $newParse -> find('tr');

			/**
			* @var string $tr_loop 	incrementing this to remove the first two rows which are useless
			**/
			$tr_loop = 0;

			// Iterating through all the rows
			foreach($trs as $tr) {

				// Eliminating the first two rows.
				$tr_loop++;
				if($tr_loop < 3) continue;
				
				// If a semester is chosen, only continue if the row matches it
				if($semester) {
					if(is_object($tr -> find('td', 0))) {
						$semester_td = $tr -> find('td', 0) -> plaintext;

						if(stripos($semester_td, $semester) === false) {
							continue;
						}
					}
				}

				// Starting the row
				echo "<tr>";

				// Getting each td
				$tds = $tr -> find('td');

				/**
				* @var string $td_loop 		incrementing this to properly bind each td to its header
				**/
				$td_loop = 1;

				/**
				* @var array $finalValues 		array that will contain all the final values to parse out
				**/
				$finalValues = array();

				// Iterating through the tds
				foreach($tds as $td) {

					// Checking if the current td is in the necessary information array
					if(in_array($td_loop, $properHeadings)) {

						// If it is, parse it out
						echo "<td>" . $td -> plaintext . "</td>";
						$headingName = array_search($td_loop, $properHeadings);
						$finalValues[$headingName] = str_replace(' ', '', $td -> plaintext);
					}

					// Increment the td loop
					$td_loop++;
				}

				/**
				* @var object $db 	object containing a database instance
				**/
				$db = new Database();

				// Inserting the rows to the database
				// Comment the below line if you don't want to add anything to the database
				$db -> insert($finalValues, self::COURSES_TABLE);

				// Ending the row
				echo "</tr>";
			}
		}

		// Ending the table
		$this -> stopScheduleTable();
	}
}
?>
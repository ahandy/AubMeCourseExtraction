<?php
abstract class ExtractScheduleBlueprint extends Config {
	protected $HTMLParsed, $dbh;
	public $properHeadings;

	abstract protected function getHeadingsRow($HTMLParsedArray);
	abstract protected function parseValues($HTMLParsedArray, $properHeadings, $semester);
	abstract protected function startScheduleTable($headings);
	abstract protected function stopScheduleTable();
}

class ExtractSchedule extends ExtractScheduleBlueprint {

	/**
	* @param string/array $file 	string or array that contains the schedule files
	* @param string       $semester fall/spring/summer 
	**/
	public function __construct($file = FALSE, $semester = false) {
		$this -> HTMLParsed = loadDOM::loadDocuments($file, true);
		$this -> getHeadingsRow($this -> HTMLParsed);
		$this -> parseValues($this -> HTMLParsed, $this -> properHeadings, $semester);
	}

	protected function getHeadingsRow($HTMLParsedArray) {
		// For the headings, only the first array is required
		if(is_array($HTMLParsedArray)) $HTMLParsed = $HTMLParsedArray[0];
		else $HTMLParsed = $HTMLParsedArray;

		if(method_exists($HTMLParsed, 'find') && is_object($HTMLParsed)) {
			/** 
			* The following will extract the headings and find their index
			* Note that this is relative to the structure of the course table on AUB's website
			* Any change in their structure will definitely require a change in the code
			**/
			$tds = $HTMLParsed -> find('table', 1) -> find('tr', 1) -> find('td');
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
				foreach($necessaryInformation as $tdTitle => &$index) {
					if($td_ == $tdTitle) {

						// This check will allow me to store the indexes for the S1/C1...
						if(strlen($index) > 0) {
							$necessaryInformation[$tdTitle . '1'] = $i;
						}

						else $index = $i;
					}
				}	

				$i++;
			}
			$properInformation = array('CRN', 'Subject', 'Code', 'ClassStartTime', 'ClassEndTime', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'RecLabStartTime', 'RecLabEndTime', 'MondayRC', 'TuesdayRC', 'WednesdayRC', 'ThursdayRC', 'FridayRC', 'SaturdayRC', "InstructorsFirstName", "InstructorsLastName");

			$this -> properHeadings = array_combine($properInformation, array_values($necessaryInformation));		
		}
	}

	protected function startScheduleTable($headings) {
		echo "<table border='1'>";
		echo "<tr>";
		foreach($headings as $heading => $index) {
			echo "<th>{$heading}</th>";
		}
		echo "</tr>";
	}

	protected function stopScheduleTable() {
		echo "</table>";
	}

	protected function parseValues($HTMLParsedArray, $properHeadings, $semester) {
		/**
		* The following will iterate through the rows inside the table and bind the information to the headings
		**/
		$this -> startScheduleTable($properHeadings);

		foreach($HTMLParsedArray as $HTMLParsed) {
			if(!is_object($HTMLParsed)) continue;
			$table_one = $HTMLParsed -> find('table', 1);
			$newParse =  str_get_html($table_one);

			$config = array(
	           'indent'         => true,
	           'output-xhtml'   => true,
	           'wrap'           => 200);

			// Tidy
			$tidy = new tidy;
			$tidy->parseString($newParse, $config, 'utf8');
			$tidy->cleanRepair();

			$newParse = tidy_get_output($tidy);
			$newParse = str_get_html($newParse);

			$trs = $newParse -> find('tr');
			$tr_loop = 0;
			foreach($trs as $tr) {
				// Eliminating the first two rows.
				$tr_loop++;
				if($tr_loop < 3) continue;
				
				if($semester) {
					if(is_object($tr -> find('td', 0))) {
						$semester_td = $tr -> find('td', 0) -> plaintext;

						if(stripos($semester_td, $semester) === false) {
							continue;
						}
					}
				}

				echo "<tr>";
				$tds = $tr -> find('td');

				$td_loop = 1;
				$finalValues = array();

				foreach($tds as $td) {
					if(in_array($td_loop, $properHeadings)) {
						echo "<td>" . $td -> plaintext . "</td>";
						$headingName = array_search($td_loop, $properHeadings);
						$finalValues[$headingName] = str_replace(' ', '', $td -> plaintext);
					}
					$td_loop++;
				}

				// Remember to clean rows (line below doesn't work)
				$db = new Database();
				$db -> insert($finalValues, self::COURSES_TABLE);
				echo "</tr>";
			}
		}
		$this -> stopScheduleTable();
	}
}
?>
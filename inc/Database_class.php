<?php
/**
* Database class that contains all the functions that might be needed by the code to facilitate some queries
**/
abstract class DatabaseBlueprint extends Config {
	public $dbh;

	abstract protected function connectToDB();
	abstract public function insert($rows, $table);
}

class Database extends DatabaseBlueprint {

	public function __construct() {
		$this -> connectToDB();
	}

	/**
	* Function that connects to the database
	**/
	protected function connectToDB() {
		try {
   			$dbh = new PDO('mysql:host=' . self::DB_HOST . ';dbname=' . self::DB_NAME, self::DB_USERNAME, self::DB_PASSWORD);
    		$dbh -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    		$this -> dbh = $dbh;
		} catch(PDOException $e) {
    		echo 'PDO Error: ' . $e->getMessage();
		}
	}

	/**
	* Insert rows into specified table
	* @param array  $rows    rows to insert with format of 'column' => 'value'
	* @param string $table   table where the rows are going to be inserted
	**/
	public function insert($rows, $table) {
		if(!is_array($rows) || !count($rows)) 
			throw new Exception("Parameter given to the insert function of the database class is not an array");

		$array_keys = array_keys($rows);

		// Removing any misc characters
		$insert_columns = implode(",", $array_keys);
		$insert_vals = ':' . implode(',:', $array_keys);
		$sql_query_string = "INSERT INTO {$table} ({$insert_columns}) VALUES ({$insert_vals})";
		$sql_query = $this -> dbh -> prepare($sql_query_string);
		$sql_query -> execute(array_combine(explode(',', $insert_vals), array_values($rows)));
	}
}
<?php

namespace Core;

class Database extends \PDO {

	public static $databases = array();

	/**
	 * Contains the total number of times that SQL statements were executed.
	 * @var int
	 */
	private $total_queries = 0;

	/**
	 * Contains statements to be executed inside a transaction at the end of the 
	 * script's execution.
	 * @var array
	 */
	private $transaction_statements = array();

	/**
	 * Adds a connection to the list of model connections
	 * 
	 * @param string $name Database configuration file to load..
	 */
	public function __construct($dsn, $username, $passwd, $options) {
		parent::__construct($dsn, $username, $passwd, $options);
	}

	public static function connectFromConfig($name) {
		require_once(BASEPATH . APP_DIR . "/$name.db.php");
		try {
			self::$databases[$name] = new Database("$type:dbname=$database;host=$host;", $username, $password, array(
				//PDO::ATTR_PERSISTENT => true
			));
			return self::$databases[$name];
		} catch(PDOException $e) {
			// Log any errors that occur when we can not connect.
			Log::error("Could not connect to database.  " . $e->getMessage(), false);
		}
		return null;
	}

	/**
	 * Executes a PDO statement.
	 * If an error occurs, logs the error.
	 * 
	 * @param PDOStatement $statement PDO statement to execute.
	 * @param array $input_parameters [optional] <p>
	 * An array of values with as many elements as there are bound
	 * parameters in the SQL statement being executed.
	 * All values are treated as <b>PDO::PARAM_STR</b>.
	 * </p>
	 * 
	 * @return bool True on successful execution, false otherwise.
	 */
	public function execute(&$statement, array $input_parameters = null) {

		// Increment the total executions variable.
		$this->total_queries++;

		// Check to see if we want to log information about the query.
		if(\Config::LOG_SQL_QUERIES) {
			Log::benchmark("query_" . $this->total_queries);
		}

		$successful = $statement->execute($input_parameters);

		if(\Config::LOG_SQL_QUERIES) {
			Log::benchmark("query_" . $this->total_queries, $statement->queryString);
		}

		if($successful) {
			return true;
		} else {
			Log::error("PDO SQL Failure: " . json_encode($statement->errorInfo()) . "\nStatement: " . $statement->queryString, true);
			return false;
		}
	}

	/**
	 * Executes a PDO statement and will return the first of the selected rows.
	 * 
	 * @param PDOStatement $statement PDO statement to execute.
	 * @return PDO::FETCH_* The fetch type.  Defaults to PDO::FETCH_ASSOC
	 */
	public function executeFetch(&$statement, $fetch_type = PDO::FETCH_ASSOC) {
		if($this->execute($statement) == false) {
			return false;
		}

		return $statement->fetch($fetch_type);
	}

	/**
	 * Executes a PDO statement and will return all the selected rows.
	 * 
	 * @param PDOStatement $statement PDO statement to execute.
	 * @return PDO::FETCH_* The fetch type.  Defaults to PDO::FETCH_ASSOC
	 */
	public function executeFetchAll(&$statement, $fetch_type = PDO::FETCH_ASSOC) {
		if($this->execute($statement) == false) {
			return false;
		}

		return $statement->fetchAll($fetch_type);
	}

	/**
	 * Executes a PDO statement and will return the last insert ID if successful.
	 * 
	 * @param PDOStatement $statement PDO statement to execute.
	 * @param array $input_parameters [optional] <p>
	 * An array of values with as many elements as there are bound
	 * parameters in the SQL statement being executed.
	 * All values are treated as <b>PDO::PARAM_STR</b>.
	 * </p>
	 * @return int Positive number if the insert was successful, -1 otherwise.
	 */
	public function executeLastInsertId(&$statement, array $input_parameters = null) {
		$exe_statement = $this->execute($statement, $input_parameters);

		if($exe_statement === true) {
			return $this->lastInsertId();
		} else {
			return -1;
		}
	}

	public function addTransactionStatement(&$statement) {
		$this->transaction_statements[] = &$statement;
	}

	public function executeTransactionStatements() {
		
		
		$this->beginTransaction();
		foreach($this->transaction_statements as $statement) {
			$this->execute($statement);
		}
		$this->commit();
		Log::debug("Executed delayed PDO statements.");
	}

}

?>

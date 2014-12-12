<?php

namespace Core;

use \PDO,
	\PDOStatement,
	\PDOException;

/**
 * Base class to all views. 
 */
class Model {

	/**
	 * Associative array of PDO connections.
	 * @var \Core\Database
	 */
	public static $db = null;

	/**
	 * Integer defining the type of query this instance is representing.
	 * 0 = UNDEFINED;
	 * 1 = SELECT;
	 * 2 = RESERVED FOR INSERT; 
	 * 3 = UPDATE;
	 * 4 = DELETE;
	 * 
	 * @var int
	 */
	private $type = 0;

	/**
	 * Column names or a single string containing "*" to retrieve all columns.
	 * 
	 * @var mixed
	 */
	private $select_columns = null;
	private $insert_values = null;
	private $update_values = null;

	/**
	 * SQL limit for the query.
	 * array(Start, Count)
	 * 
	 * @var array
	 */
	private $limit = null;

	/**
	 * Contains all the where statements for a query.
	 * 
	 * @var array
	 */
	private $where = array();

	/**
	 * Column names to group results by.
	 * 
	 * @var array
	 */
	private $group_by = array();

	/**
	 * True to have DISTINCT added to the select statement.
	 * 
	 * @var bool
	 */
	private $distinct = false;

	/**
	 * Array of orders for the select statement.
	 * 
	 * @var array
	 */
	private $order_by = array();

	/**
	 * Array of tables and columns 
	 * 
	 * @var array
	 */
	private $join = array();

	/**
	 * Array of all the parameter names and their values for this query.
	 * @var array
	 */
	private $params = array();

	/**
	 * 
	 * @return \Core\Model
	 */
	public function distinct() {
		$this->distinct = true;

		return $this;
	}

	/**
	 * 
	 * @param type $column
	 * @param type $value
	 * @param type $relational_operator
	 * @param type $operator
	 * @return \Core\Model
	 */
	public function where($column, $value, $relational_operator = "=", $operator = "AND") {
		if(count($this->where) > 0) {
			$this->where[] = array(0, $operator);
		}
		$this->where[] = array(1, $column, $value, $relational_operator);

		return $this;
	}

	/**
	 * 
	 * @param type $column
	 * @param type $values
	 * @param type $not
	 * @param type $operator
	 * @return \Core\Model
	 */
	public function whereIn($column, $values, $not = false, $operator = "AND") {
		if(count($this->where) > 0) {
			$this->where[] = array(0, $operator);
		}
		$this->where[] = array(2, $column, $values, $not);

		return $this;
	}

	/**
	 * NOTE: Only one group by can be used per query.
	 * 
	 * @param type $columns
	 * @return \Core\Model
	 */
	public function groupBy($columns) {
		$this->group_by = (is_array($columns)) ? $columns : array($columns);

		return $this;
	}

	/**
	 * 
	 * @param type $column
	 * @param type $direction
	 * @return \Core\Model
	 */
	public function orderBy($column, $direction = "ASC") {
		$this->order_by[] = array($column, $direction);

		return $this;
	}

	/**
	 * 
	 * @param type $this_column
	 * @param type $other_full_column
	 * @param type $relational_operator
	 * @param type $type
	 * @return \Core\Model
	 */
	public function join($this_column, $other_full_column = null, $relational_operator = "=", $type = "LEFT") {
		// Breakup the column's name and table.
		if($other_full_column === null) {
			$other_col_parts = explode("_", $this_column);
		} else {
			$other_col_parts = explode(".", $other_full_column);
		}

		$this->join[] = array($this_column, $other_col_parts[0], $other_col_parts[1], $relational_operator, $type);
		return $this;
	}

	/**
	 * 
	 * @param type $start
	 * @param type $count
	 * @return \Core\Model
	 */
	public function limit($start, $count) {
		$this->limit = array($start, $count);

		return $this;
	}

	private function createStatement() {
		$sql = null;
		switch($this->type) {
			case 1: // SELECT
				$sql = "SELECT " . (($this->distinct) ? "DISTINCT" : "") . "\n\t";

				// Add the selected columns
				if(is_array($this->select_columns)) {
					$sql .= implode(",\n\t", $this->select_columns);
				} else {
					$sql .= $this->select_columns;
				}

				// FROM
				$sql .= "\nFROM " . static::$name . "\n";
				break;

			case 2: // INSERT
				$columns = array_keys($this->insert_values);
				$sql = "INSERT INTO " . static::$name . " (" . implode(', ', $columns) . ")\n";
				$sql .= "VALUES (:" . implode(', :', $columns) . ")";

				foreach($this->insert_values as $column => $value) {
					$this->params[":" . $column] = $value;
				}

				unset($this->insert_values);
				break;

			case 3: // UPDATE
				$sql = "UPDATE " . static::$name . " SET ";
				$update_value_count = count($this->update_values);
				foreach($this->update_values as $column => $value) {
					$this->params[":" . $column] = $value;
					$sql .= $column . " = :" . $column;

					if(--$update_value_count > 0) {
						$sql .= ",\n\t";
					}
				}
				$sql .= "\n";
				unset($this->update_values);
				break;

			case 4: // DELETE
				$sql = "DELETE FROM " . static::$name . "\n";
				break;
		}

		// JOIN
		$join_count = count($this->join);
		if($join_count != 0) {
			foreach($this->join as $join) {
				$sql .= $join[4] . " JOIN " . $join[1] . " AS J" . $join[0] . "\n";
				$sql .= "\tON " . static::$name . "." . $join[0] . $join[3] . "J" . $join[0] . "." . $join[2] . "\n";
			}
		}

		// WHERE
		if(count($this->where) > 0) {
			$sql .= "WHERE\n";
			$param_count = 0;
			foreach($this->where as $value) {
				switch($value[0]) {
					case 0: // AND/OR
						$sql .= " " . $value[1] . "\n";
						break;

					case 1: // WHERE
						$param_name = ":param_" . $param_count++;
						$sql .= "\t" . $value[1] . " " . $value[3] . " " . $param_name;
						$this->params[$param_name] = $value[2];
						break;

					case 2: // IN
						//array(2, $column, $values, $not);
						$sql .= "\t" . $value[1] . (($value[3]) ? " NOT" : "") . " IN(";
						$values_count = count($value[2]);

						for($i = 0; $i < $values_count; $i++) {
							$param_name = ":param_" . $param_count++;

							// Set the parameters for use later.
							$this->params[$param_name] = $value[2][$i];
							$sql .= $param_name;
							if($i + 1 < $values_count) {
								$sql .= ", ";
							} else {
								$sql .= ")";
							}
						}
						break;
				}
			}
			$sql .= "\n";
		}



		// GROUP BY
		$group_by_count = count($this->group_by);
		if($group_by_count != 0) {
			$sql .= "GROUP BY ";
			foreach($this->group_by as $group) {
				$sql .= $group;
				// Check to see if this is the last group array element.
				if(--$group_by_count > 0) {
					$sql .= ",\n\t";
				} else {
					$sql .= "\n";
				}
			}
		}

		// ORDER BY
		$order_by_count = count($this->order_by);
		if($order_by_count != 0) {
			$sql .= "ORDER BY ";
			foreach($this->order_by as $join) {
				if($join[1] === null) {
					$sql .= $join[0];
				} else {
					$sql .= $join[0] . " " . $join[1];
				}
				// Check to see if this is the last order array element.
				if(--$order_by_count > 0) {
					$sql .= ",\n\t";
				} else {
					$sql .= "\n";
				}
			}
		}

		// LIMIT
		if($this->limit !== null) {
			$sql .= "LIMIT " . $this->limit[0] . ", " . $this->limit[1];
		}

		// Statement generation.
		$statement = self::$db->prepare($sql);
		if(count($this->params) > 0) {
			foreach($this->params as $name => $value) {
				$statement->bindValue($name, $value);
			}
		}

		return $statement;
	}

	/**
	 * Executes a PDO statement.
	 * If an error occurs, logs the error.
	 * 
	 * @return bool True on successful execution, false otherwise.
	 */
	public function execute() {
		$statement = $this->createStatement();
		return self::$db->execute($statement);
	}

	public function executeTransaction() {
		$statement = $this->createStatement();
		self::$db->addTransactionStatement($statement);
	}

	/**
	 * Executes the generated statement and will return the first of the selected rows.
	 * 
	 * @return PDO::FETCH_* The fetch type.  Defaults to PDO::FETCH_ASSOC
	 */
	public function executeFetch($fetch_type = PDO::FETCH_ASSOC) {
		$statement = $this->createStatement();
		return self::$db->executeFetch($statement, $fetch_type);
	}

	/**
	 * Executes the generated statement and will return all the selected rows.
	 * 
	 * @return PDO::FETCH_* The fetch type.  Defaults to PDO::FETCH_ASSOC
	 */
	public function executeFetchAll($fetch_type = PDO::FETCH_ASSOC) {
		$statement = $this->createStatement();
		return self::$db->executeFetchAll($statement, $fetch_type);
	}

	/**
	 * Executes the query and returns the new row id.
	 * 
	 * @return int New row id or -1 on failure.
	 */
	public function executeInsertId() {
		if($this->execute() == false) {
			return -1;
		}
		return self::$db->lastInsertId();
	}

	/**
	 * Gets the new row id for the last inserted row.
	 * 
	 * @return int New row id.
	 */
	public function insertId() {
		return self::$db->lastInsertId();
	}

	/**
	 * 
	 * @param mixed $columns Array of column names to select or "*" to specify all columns.
	 * @return \Core\Model
	 */
	public static function select($columns = "*") {
		/** @var \Core\Model Child sub-class */
		$model = new static();

		$model->type = 1;
		$model->select_columns = $columns;

		return $model;
	}

	/**
	 * Creates a single row in the table.
	 *
	 * @param array $inputs Associative array of COLUMN_NAME => VALUE <br>
	 * @return \Core\Model
	 */
	public static function insert($inputs) {
		$model = new static();
		$model->type = 2;
		$model->insert_values = $inputs;

		return $model;
	}

	/**
	 * Updates a single row in the table.
	 *
	 * @param array $inputs Associative array of COLUMN_NAME => VALUE
	 * @param string $id Row identifier.
	 * @return \Core\Model
	 */
	public static function update($inputs) {
		$model = new static();
		$model->type = 3;
		$model->update_values = $inputs;

		return $model;
	}

	/**
	 * Removes the specified row from the table.
	 *
	 * @return \Core\Model
	 */
	public static function delete() {
		$model = new static();
		$model->type = 4;

		return $model;
	}

}

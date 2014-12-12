<?php

namespace Core;

/**
 * Base class to all views. 
 */
class QueryExecuterLib {

	/**
	 * Integer defining the type of query this instance is representing.
	 * 0 = UNDEFINED;
	 * 1 = SELECT;
	 * 2 = RESERVED FOR INSERT; 
	 * 3 = UPDATE;
	 * 
	 * @var int
	 */
	private $type = 0;

	/**
	 * Table for this query.
	 * @var string
	 */
	private $table;

	/**
	 * Column names or a single string containing "*" to retrieve all columns.
	 * 
	 * @var mixed
	 */
	private $select_columns = null;

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

	public function __construct($table) {
		$this->table = $table;
	}

	/**
	 * Initial method to start a select query.
	 * 
	 * @param mixed $columns Array of columns or a string for the select columns.
	 */

	/**
	 * 
	 * @param type $columns
	 * @return \Core\QueryExecuterLib
	 */
	public function select($columns = "*") {
		$this->type = 1;
		$this->select_columns = $columns;

		return $this;
	}

	/**
	 * 
	 * @return \Core\QueryExecuterLib
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
	 * @return \Core\QueryExecuterLib
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
	 * @return \Core\QueryExecuterLib
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
	 * @return \Core\QueryExecuterLib
	 */
	public function groupBy($columns) {
		$this->group_by = (is_array($columns)) ? $columns : array($columns);

		return $this;
	}

	/**
	 * 
	 * @param type $column
	 * @param type $direction
	 * @return \Core\QueryExecuterLib
	 */
	public function orderBy($column, $direction) {
		$this->order_by[] = array($column, $direction);

		return $this;
	}

	/**
	 * 
	 * @param type $this_column
	 * @param type $other_full_column
	 * @param type $relational_operator
	 * @param type $type
	 * @return \Core\QueryExecuterLib
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
	 * @return \Core\QueryExecuterLib
	 */
	public function limit($start, $count) {
		$this->limit = array($start, $count);
		return $this;
	}

	public function generateSql() {
		$sql = "SELECT " . (($this->distinct) ? "DISTINCT" : "") . "\n\t";
		$param_count = 0;

		if(is_array($this->select_columns)) {
			$sql .= implode(",\n\t", $this->select_columns) . "\n";
		} else {
			$sql .= $this->select_columns;
		}

		// FROM
		$sql .= "FROM " . $this->table . "\n";

		// JOIN
		$join_count = count($this->join);
		if($join_count != 0) {
			foreach($this->join as $join) {
				$sql .= $join[4] . " JOIN " . $join[1] . " AS J" . $join[0] . "\n";
				$sql .= "\tON " . $this->table . "." . $join[0] . $join[3] . "J" . $join[0] . "." . $join[2] . "\n";
			}
		}

		// WHERE
		if(count($this->where)) {
			$sql .= "WHERE\n";
			foreach($this->where as $where) {
				switch($where[0]) {
					case 0: // AND/OR
						$sql .= " " . $where[1] . "\n";
						break;

					case 1: // WHERE
						$sql .= "\t" . $where[1] . " " . $where[3] . " :param_" . $param_count++;
						break;

					case 2: // IN
						//array(2, $column, $values, $not);
						$sql .= "\t" . $where[1] . (($where[3]) ? " NOT" : "") . " IN(";
						$values_count = count($where[2]);

						for($i = 0; $i < $values_count; $i++) {
							$sql .= ":param_" . $param_count++;
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
				$sql .= $join[0] . " " . $join[1];
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
			$sql .= "LIMIT " . $this->limit[0] . " " . $this->limit[1];
		}

		return $sql;
	}

}
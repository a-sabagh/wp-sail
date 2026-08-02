<?php

namespace WPSail\Database;

use Aimeos\Macro\Macroable;
use BadMethodCallException;

class Builder {

	use Macroable;

	private $wpdb;
	private $table;
	private $distinct;
	private $column=[ '*' ];
	private $join=[];
	private $where=[];
	private $where_in=[];
	private $where_between=[];
	private $order_by=[];
	private $group_by;
	private $offset;
	private $limit;

	public $query;
	public $prefix;

	/**
	 * Initialize the QueryBuilder with the global WordPress database object.
	 */
	public function __construct(){
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->prefix = $wpdb->prefix;
	}

    /**
     * Handle static method calls dynamically.
     *
     * @param string $method The name of the static method being called.
     * @param array $parameters The parameters passed to the static method.
     * @return mixed
     */
    public static function __callStatic($method, $parameters) {
        // Create a new instance of the QueryBuilder
        $instance = new static();

		if( ! method_exists($instance, $method) ){
        	// Add logic for handling undefined instance methods if needed
			throw new BadMethodCallException( sprintf( __("Method %s does not exist.",'wore'), $method ) );
		}

        // Forward the call to the instance method
        return $instance->$method(...$parameters);
    }

	/**
	 * Set the table to be used for the query.
	 *
	 * Automatically appends the WordPress database prefix to the table name.
	 *
	 * @param string $table The name of the table.
	 * @return $this
	 */
	public function table($table){
		$this->table=$this->prefix . $table;
		return $this;
	}

	/**
	 * Enable the DISTINCT modifier for the query.
	 *
	 * @return $this
	 */
	public function distinct(){
		$this->distinct=true;
		return $this;
	}

	/**
	 * Specify the columns to be retrieved in the query.
	 *
	 * @param mixed ...$column The columns to select.
	 * @return $this
	 */
	public function select(...$column){
		$this->column = array_map(
			function($element){ 
				return ($this->alias_regex($element))? $this->prefix . $element : $element; 
			},func_get_args());
		return $this;
	}

	/**
	 * Add a RIGHT JOIN clause to the query.
	 *
	 * @param string $table_name The table to join.
	 * @param array $condition The join condition.
	 * @return $this
	 */
	public function right_join($table_name,$condition=[]){
		$this->join($table_name,$condition,'right');
		return $this;
	}

	/**
	 * Add a LEFT JOIN clause to the query.
	 *
	 * @param string $table_name The table to join.
	 * @param array $condition The join condition.
	 * @return $this
	 */
	public function left_join($table_name,$condition=[]){
		$this->join($table_name,$condition,'left');
		return $this;
	}

	/**
	 * Add a JOIN clause to the query.
	 *
	 * @param string $table_name The table to join.
	 * @param array $condition The join condition.
	 * @param string $type The type of join (e.g., INNER, LEFT, RIGHT).
	 * @return $this
	 */
	public function join($table_name,$condition,$type){
		$type = strtoupper($type) ?: 'INNER';
		$table = $this->prefix . $table_name;
		$condition = array_map(
			function($element){ 
				return($this->alias_regex($element))? $this->prefix . $element : $element; 
			},$condition);
		$this->join[]=[$table,$condition,$type];
		return $this;
	}

	/**
	 * Add a WHERE BETWEEN condition to the query.
	 *
	 * @param string $column The column name.
	 * @param mixed $begin The start value.
	 * @param mixed $end The end value.
	 * @return $this
	 */
	public function where_between($column, $begin, $end){
		$this->where_between[] = [$column,$begin,$end];
		return $this;
	}

	/**
	 * Add a WHERE condition to the query.
	 *
	 * @param string $first_operand The first operand.
	 * @param string $operator The comparison operator.
	 * @param mixed $second_operand The second operand.
	 * @param string $type The type of condition (default is '').
	 * @return $this
	 */
	public function where($first_operand,$operator,$second_operand,$type=''){
		$type = strtoupper($type);
		$second_operand = ('integer' != gettype($second_operand))? "'{$second_operand}'" : $second_operand;
		$first_operand = ($this->alias_regex($first_operand))? $this->prefix . $first_operand : $first_operand;
		$this->where[] = [$first_operand,$operator,$second_operand,$type];
		return $this;
	}

	/**
	 * Add an OR WHERE condition to the query.
	 *
	 * @param string $first_operand The first operand.
	 * @param string $operator The comparison operator.
	 * @param mixed $second_operand The second operand.
	 * @return $this
	 */
	public function or_where($first_operand,$operator,$second_operand){
		$this->where($first_operand,$operator,$second_operand,'or');
		return $this;
	}

	/**
	 * Add a WHERE IN condition to the query.
	 *
	 * @param string $column The column name.
	 * @param array $range The array of values for the IN condition.
	 * @return $this
	 */
	public function where_in($column,$range=[]){
		$values = array_map(function($element){
			return ('integer' != gettype($element))? "'{$element}'" : $element;
		},$range);
		$this->where_in[] = [$column,$values];
		return $this;
	}

	/**
	 * Add an ORDER BY clause to the query.
	 *
	 * @param string $order_by The column to order by.
	 * @param string $order The direction of the order (e.g., ASC or DESC).
	 * @return $this
	 */
	public function order_by($order_by,$order){
		$this->order_by[]=func_get_args();
		return $this;
	}
	
	/**
	 * Add a DESCENDING ORDER BY clause to the query.
	 *
	 * @param string $order_by The column to order by.
	 * @return $this
	 */
	public function order_by_desc($order_by){
		$this->order_by[] = [$order_by,'desc'];
		return $this;
	}

	/**
	 * Add a GROUP BY clause to the query.
	 *
	 * @param string $group_column The column to group by.
	 * @return $this
	 */
	public function group_by($group_column){
		$this->group_by = $group_column;
		return $this;
	}

	/**
	 * Set the offset for the query results.
	 *
	 * @param int $offset The number of rows to skip.
	 * @return $this
	 */
	public function offset($offset){
		$this->offset = $offset;
		return $this;
	}

	/**
	 * Set the limit for the query results.
	 *
	 * @param int $limit The maximum number of rows to retrieve.
	 * @return $this
	 */
	public function limit($limit){
		$this->limit = $limit;
		return $this;
	}

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function take($value) {
        return $this->limit($value);
    }
	
	/* CRUD */
	
	/**
	 * Execute a SELECT query and return the results.
	 *
	 * @return array The query results as an associative array.
	 */
	public function get(){
		$table = $this->table;
		$columns = implode(',',$this->column);
		$distinct = $this->distinct ? 'DISTINCT' : '';
		$query = "SELECT {$distinct} {$columns} FROM {$table} ";
		if(!empty($this->join)){
			foreach($this->join as $join){
				$table = current($join);
				$condition = next($join);
				$operand1 = current($condition);
				$operand2 = next($condition);
				$type = next($join);
				$query .= "{$type}  JOIN {$table} ON {$operand1} = {$operand2} ";
			}
		}
		$this->prepare_where_logic($query);
		$group_by = $this->group_by;
		$query .= (!empty($group_by))? "GROUP BY {$group_by} " : "";
		if(!empty($this->order_by)){
			$order_by = implode(",",array_map(function($element){ return implode(' ',$element); },$this->order_by));
			$query .= "ORDER BY {$order_by} ";
		}
		$limit = (int) $this->limit;
		$query .= ($limit > 0)? "LIMIT {$limit} " : "";
		$offset = (int) $this->offset;
		$query .= ($limit > 0)? "OFFSET {$offset} " : "";
		$this->query = $query;
		return $this->wpdb->get_results($query,ARRAY_A);
	}

	/**
	 * alias for get retrieve all records
	 *
	 * @return array The query results as an associative array.
	 */
	public function all() {
		return $this->get();
	}

	/**
	 * Execute SELECT query on first result row
	 *
	 * @return array
	 */
	public function first() {
		$result = $this->limit(1)->get();
		return !empty($result) ? current($result) : false;
	}

	/**
	 * find entity by primary key
	 *
	 * @param string $name primary key column name
	 * @param int|string $key primary key value
	 * @return array
	 */
	public function find($name, $key) {
		$this->where($name, '=', $key)->first();
	}

	/**
	 * Update rows in the table with the specified data and conditions.
	 *
	 * @param array $data The data to update as an associative array.
	 * @return int|false The number of affected rows, or false on failure.
	 */
	public function update($data){
		$table = $this->table;
		$query = "UPDATE {$table} SET ";
		$data = array_map(function ($key,$value){
			if(is_null($value)){
				$value_finalized = 'NULL';
			}else{
				$value_finalized = ('integer' == gettype($value))? $value : "'{$value}'";
			}
			return "{$key}={$value_finalized}";
		},array_keys($data),$data);
		$update_data_query = implode(',',$data);
		$query .= "{$update_data_query} ";
		$this->prepare_where_logic($query);
		$this->query = $query;
		return $this->wpdb->query($query);
	}

	/**
	 * Delete rows from the table based on the specified conditions.
	 *
	 * @return int|false The number of affected rows, or false on failure.
	 */
	public function delete(){
		$table = $this->table;
		$query = "DELETE FROM {$table} ";
		$this->prepare_where_logic($query);
		$this->query = $query;
		return $this->wpdb->query($query);
	}

	/**
	 * Insert a new row into the table.
	 *
	 * @param array $data The data to insert as an associative array.
	 * @return int|false The ID of the inserted row, or false on failure.
	 */
	public function insert($data){
		$this->wpdb->insert($this->table,$data);
		return $this->wpdb->insert_id;
	}

	/**
	 * alias for insert method
	 *
	 * @return int|false The ID of the inserted row, or false on failure.
	 * @return void
	 */
	public function create($data) {
		$this->insert($data);
	}
	
	/**
	 * Truncate the table, removing all rows and resetting the auto-increment value.
	 *
	 * @return int|false The number of affected rows, or false on failure.
	 */
    public function truncate(){
        $table = $this->table;
        $query = "TRUNCATE TABLE {$table}";
        $this->query = $query;
        return $this->wpdb->query($query);
    }

	/**
	 * Execute a raw SQL query without processing the result.
	 *
	 * @param string $sql The raw SQL query to execute.
	 * @return int|false The number of affected rows, or false on failure.
	 */
	public function statement($sql){
		$this->query = $sql;
		return $this->wpdb->query($sql);
	}
    
	/* Logics */

	/**
	 * Compile all WHERE-related conditions and append them to the query.
	 *
	 * This method processes conditions from WHERE, WHERE IN, and WHERE BETWEEN clauses
	 * and builds them into the query string.
	 *
	 * @param string &$query The query string to which the conditions will be appended.
	 * @return string The modified query string.
	 */
	private function prepare_where_logic(&$query){
		$condition_count = 0;
		if(!empty($this->where)){
			foreach($this->where as $condition){
				$type = end($condition);
				if(0 == $condition_count) $logic = "WHERE "; else $logic = ('OR' == strtoupper($type))? "OR " : "AND ";
				reset($condition);
				$operand1 = current($condition);
				$operator = next($condition); 
				$operand2 = next($condition);
				$query .= "{$logic} {$operand1} {$operator} {$operand2} ";
				$condition_count++;
			} 	
		}
		if(!empty($this->where_in)){
			foreach($this->where_in as $condition){
				$logic = ($condition_count > 0)? "AND " : "WHERE ";
				$column = current($condition);
				$values = implode(',',end($condition));
				$query .= "{$logic} {$column} IN ( {$values} ) ";
				$condition_count++;
			}
		}
		if(!empty($this->where_between)){
			foreach($this->where_between as [$column,$begin,$end]){
				$logic = ($condition_count > 0)? "AND " : "WHERE ";
				$begin_formatted = is_numeric($begin)? $begin : "'{$begin}'";
				$end_formatted = is_numeric($end)? $end : "'{$end}'";
				$query .= "{$logic} {$column} BETWEEN {$begin_formatted} AND {$end_formatted} ";
				$condition_count++;
			}
		}
		return $query;
	}

	/**
	 * Check if the query string contains a table alias.
	 *
	 * @param string $query_string The query string to check.
	 * @return bool True if an alias exists, false otherwise.
	 */
	public function alias_regex(string $query_string){
		$query_string_tolower = strtolower($query_string);
		return strpos('.',$query_string_tolower) 
			&& !strpos('as',$query_string_tolower);	
	}

}

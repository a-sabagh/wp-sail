<?php

namespace WOAP\Packages;

class QueryBuilder {

	private $wpdb;
	private $table;
	private $distinct;
	private $column=[];
	private $join=[];
	private $where=[];
	private $where_in=[];
	private $order_by=[];
	private $group_by;
	private $offset;
	private $limit;

	public $query;
	public $prefix;

	public function __construct(){
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->prefix = $wpdb->prefix;
	}

	public function table($table){
		$this->table=$this->prefix . $table;
		return $this;
	}

	public function distinct(){
		$this->distinct=true;
		return $this;
	}

	public function select(...$column){
		$this->column=array_map(function($element){ return (strpos($element,'.'))? $this->prefix . $element : $element; },func_get_args());
		return $this;
	}

	public function right_join($table_name,$condition=[]){
		$this->join($table_name,$condition,'right');
		return $this;
	}

	public function left_join($table_name,$condition=[]){
		$this->join($table_name,$condition,'left');
		return $this;
	}

	public function join($table_name,$condition,$type){
		$type = strtoupper($type) ?: 'INNER';
		$table = $this->prefix . $table_name;
		$condition = array_map(function($element){ return(strpos($element,'.'))? $this->prefix . $element : $element; },$condition);
		$this->join[]=[$table,$condition,$type];
		return $this;
	}

	public function where($first_operand,$operator,$second_operand,$type=''){
		$type = strtoupper($type);
		$second_operand = ('integer' != gettype($second_operand))? "'{$second_operand}'" : $second_operand;
		$first_operand = (strpos($first_operand,'.'))? $this->prefix . $first_operand : $first_operand;
		$this->where[] = [$first_operand,$operator,$second_operand,$type];
		return $this;
	}

	public function or_where($first_operand,$operator,$second_operand){
		$this->where($first_operand,$operator,$second_operand,'or');
		return $this;
	}

	public function where_in($column,$range=[]){
		$values = array_map(function($element){
			return ('integer' != gettype($element))? "'{$element}'" : $element;
		},$range);
		$this->where_in[] = [$column,$values];
		return $this;
	}

	public function order_by($order_by,$order){
		$this->order_by[]=func_get_args();
		return $this;
	}

	public function group_by($group_column){
		$this->group_by = $group_column;
		return $this;
	}

	public function offset($offset){
		$this->offset = $offset;
		return $this;
	}

	public function limit($limit){
		$this->limit = $limit;
		return $this;
	}

	/* CRUD */
	
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

	public function update($data){
		$table = $this->table;
		$query = "UPDATE {$table} SET ";
		$data = array_map(function ($key,$value){
			$value_finalized = ('integer' != gettype($value))? "'{$value}'" : $value;
			return "{$key}={$value_finalized}";
		},array_keys($data),$data);
		$update_data_query = implode(',',$data);
		$query .= "{$update_data_query} ";
		$this->prepare_where_logic($query);
		$this->query = $query;
		return $this->wpdb->query($query);
	}

	public function delete(){
		$table = $this->table;
		$query = "DELETE FROM {$table} ";
		$this->prepare_where_logic($query);
		$this->query = $query;
		return $this->wpdb->query($query);
	}

	public function insert($data){
		$this->wpdb->insert($this->table,$data);
		return $this->wpdb->insert_id;
	}

	/* Logics */

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
		return $query;
	}

}

<?php 

namespace SAIL\Models;

use WP_Error;
use SAIL\Packages\Database\Model;
use SAIL\Packages\Database\QueryBuilder;
use SAIL\Resources\SessionResource;

class Session extends Model {

	protected $table;
	protected $table_meta;

	protected $hidden = [ 'created_at', 'updated_at' ];

	protected $fillable = [
		'identifier', 'user_id', 'user_agent', 'ip_address', 'request_endpoint', 'payload',
	];

	protected $data = [
		'identifier' => 'guest',
		'user_id' => 0,
		'user_agent' => '',
		'ip_address' => '',
		'request_endpoint' => '',
		'payload' => '',
		'created_at' => '',
		'updated_at' => '',
	];


	public function __construct(array $data=[]){
		parent::__construct($data);
		$this->table = 'sail_session';
	}

	public function create (array $data=null){
		$data = $data ?: $this->data;
		$query_builder = new QueryBuilder;
		$id = $query_builder->table($this->table)->insert($data);
		return $id ?: new WP_Error(500,__CLASS__ . 'insertion failed');
	}

	public function find($identifier){
		$query_builder = new QueryBuilder;
		$session_rows = $query_builder->table($this->table)->select('*')->where('identifier','=',$identifier)->get();
		if(empty($session_rows)){
			return false;
		}
		$session_row = current($session_rows);
		$session_resource = new SessionResource;
		return $session_resource->set_id($session_row['id'])->set_data($session_row);
	}

	public function update(int $id=null, array $data=null){
		$query_builder = new QueryBuilder;
		$id = is_numeric($id)? $id : $this->get_id();
		$data = $data ?: $this->get_data();
		return $query_builder->table($this->table)->where('id','=',$id)->update($data);
	}

	public function delete(int $id=null){
		$id = $id ?? $this->id;
		$this->query_builder()->table($this->table)->where('id','=',$id)->delete();
	}

}

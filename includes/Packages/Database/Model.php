<?php

namespace SAIL\Packages\Database;

use Exception;

class Model {

	public $id;
	protected $data = [];
	protected $hidden = [];
	protected $fillable = [];
	protected $query_builder;

    public function __construct($data = null){
		if(isset($data) && is_array($data)){
			$this->set_data($data);
		}elseif(isset($data) && is_numeric($data) && $data > 0){
			$id = $data;
			$object = $this->find($id);
			if(is_object($object) && $object instanceof self){
				$this->set_id($id)->set_data($object->get_data());
			}
		}
    }

	public function query_builder(){
		return new QueryBuilder;
	}

    public function get_id(){
        return $this->id;
    }

	public function set_id(int $id){
		$this->id = $id;
		return $this;
	}

	private function get_fillable(){
		return $this->fillable;
	}

	private function set_fillable(array $fillable){
		$this->fillable = $fillable;
	}

	private function get_hidden(){
		return $this->hidden;
	}

	private function set_hidden(array $hidden){
		$this->hidden = $hidden;	
	}

    public function set_data(array $data){
		foreach($this->data as $key => $value){
			$this->data[$key] = 
				false !== array_search($key,array_keys($data))? 
				$data[$key] : 
				$value;
		}
		return $this;
    }

    public function get_data($index=null){
		$prefix = strtolower(str_replace('\\','_',get_class($this)));
		return (isset($index))? 
			apply_filters("{$prefix}_{$index}_item_data", $this->data[$index], true) :
		   	apply_filters("{$prefix}_data", $this->data, true);
    }

	private function prepare_mass_assignment(){
		$mass_assignment = $this->data;
		$fillable = $this->get_fillable();
		foreach( $mass_assignment as $key => $value ){
			if(!in_array($key,$fillable)){
				unset($mass_assignment[$key]);	
			}
		}
		return $mass_assignment;
	}	

    public function save(int $id=null){
		$mass_assignment = $this->prepare_mass_assignment();
        if(is_numeric($this->id)){
            $result = $this->update( $this->id, $mass_assignment );
			return ($result)? $this->id : false;
        }else{
            $this->id = $this->create( $mass_assignment );
			return $this->id;
        }
    }

    public function remove(){
        $this->delete($this->id);
    }

    protected function create(array $data=[]){}
    protected function update(int $id=null,array $data=[]){}
    public function delete(int $id=null){}

}

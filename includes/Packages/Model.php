<?php

namespace WOAP\Packages;

class Model {

	public $id;
	protected $data;
	protected $query_builder;

    public function __construct(array $data = array()){
        $this->set_data($data);
		$this->query_builder = new QueryBuilder;
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

    public function set_data(array $data){
		foreach($this->data as $key => $value){
			$this->data[$key] = $data[$key] ?? $value;
		}
		return $this;
    }

    public function get_data($index=null){
		$prefix = strtolower(str_replace('\\','_',get_class($this)));
		return (isset($index))? 
			apply_filters("{$prefix}_{$index}_item_data",$this->data[$index]) :
		   	apply_filters("{$prefix}_data",$this->data);
    }

    public function save(int $id=null){
        if(is_numeric($this->id)){
            $result = $this->update($this->id,$this->data);
			return ($result)? $this->id : false;
        }else{
            $this->id = $this->create($this->data);
			return $this->id;
        }
    }

    public function remove(){
        $this->delete($this->id);
    }

    public function create(array $data=[]){}
    public function update(int $id=null,array $data=[]){}
    public function delete(int $id=null){}

}

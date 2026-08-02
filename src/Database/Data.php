<?php

namespace WPSail\Database;

abstract class Data {

	public int $id;

	protected array $data;
	
	protected Builder $builder;

	/**
	 * Initialize the data object from an array or identifier.
	 *
	 * @param array|int|null $data Initial field data or an object identifier.
	 */
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

	/**
	 * Find a data object by its identifier.
	 *
	 * @param int $id The object identifier.
	 * @return mixed
	 */
	abstract public function find(int $id);

	/**
	 * Create a new database query builder.
	 *
	 * @return Builder
	 */
	public function query(){
		return new Builder;
	}

	/**
	 * Get the object identifier.
	 *
	 * @return int
	 */
    public function get_id(){
        return $this->id;
    }

	/**
	 * Set the object identifier.
	 *
	 * @param int $id The object identifier.
	 * @return $this
	 */
	public function set_id(int $id){
		$this->id = $id;
		return $this;
	}

	/**
	 * Update the object's known data fields.
	 *
	 * @param array $data The data to assign.
	 * @return $this
	 */
    public function set_data(array $data){
		foreach($this->data as $key => $value){
			$this->data[$key] = 
				false !== array_search($key,array_keys($data))? 
				$data[$key] : 
				$value;
		}
		return $this;
    }

	/**
	 * Get all object data or a single filtered field.
	 *
	 * @param string|null $index The field name, or null for all fields.
	 * @return mixed
	 */
    public function get_data($index=null){
		$prefix = strtolower(str_replace('\\','_',get_class($this)));
		return (isset($index))? 
			apply_filters("{$prefix}_{$index}_item_data",$this->data[$index]) :
		   	apply_filters("{$prefix}_data",$this->data);
    }

	/**
	 * Create or update the object in persistent storage.
	 *
	 * @param int|null $id An optional object identifier.
	 * @return int|false
	 */
    public function save(int $id=null){
        if(is_numeric($this->id)){
            $result = $this->update($this->id,$this->data);
			return ($result)? $this->id : false;
        }else{
            $this->id = $this->create($this->data);
			return $this->id;
        }
    }

	/**
	 * Remove the object from persistent storage.
	 *
	 * @return void
	 */
    public function remove(){
        $this->delete($this->id);
    }

	/**
	 * Create an object in persistent storage.
	 *
	 * @param array $data The data to create.
	 * @return int|false
	 */
    abstract public function create(array $data=[]);
    
	/**
	 * Update an object in persistent storage.
	 *
	 * @param int|null $id The object identifier.
	 * @param array $data The updated data.
	 * @return int|false
	 */
	abstract public function update(int $id=null,array $data=[]);
    
	/**
	 * Delete an object from persistent storage.
	 *
	 * @param int|null $id The object identifier.
	 * @return int|false
	 */
	abstract public function delete(int $id=null);
}

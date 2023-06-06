<?php

namespace WOAP\Packages;

class Repository {
	
	private $repository;

	public function get($class){
		if(!isset($this->repository[$class])){
			$this->repository[$class] = new $class;
		}
		return $this->repository[$class];
	}

	public function remove($class){
		unset($this->repository[$class]);
	}

	public function set($class,$object){
		$this->repository[$class] = $object;
	}

}


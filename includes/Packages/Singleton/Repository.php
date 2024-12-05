<?php

namespace SAIL\Packages\Singleton;

class Repository {
	
	private $repository;

	public function get($class,array $arguments = []){
		if(!isset($this->repository[$class])){
			$this->repository[$class] = new $class(...$arguments);
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


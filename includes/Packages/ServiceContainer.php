<?php

namespace WOAP\Packages;

defined('ABSPATH') || exit;

class ServiceContainer {

    public $modules;

    public function __construct($services) {
        $this->require_services($services);
        $this->load_services($services);
    }
    
    public function get($class){
        return $this->modules[$class];
    }
    
    public function require_services($services){
        foreach($services as $class => $path){
            require_once $path;
        }
    }
    
    public function load_services($services){
        foreach($services as $class => $path){
            $this->modules[$class] = new $class($this);
        }
    }
    
}

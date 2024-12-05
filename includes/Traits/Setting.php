<?php

namespace SAIL\Traits;

trait Setting {

	public function get_option(string $key=null){
		$options = get_option(self::option);
		if( isset($key) ){
			return isset($options[$key])? $options[$key] : null;
		}
		return $options;
	}

}

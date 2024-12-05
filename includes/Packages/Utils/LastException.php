<?php

namespace SAIL\Packages\Utils;

class LastException {

	public $exception_map = [
        E_ERROR => "E_ERROR",
        E_WARNING => "E_WARNING",
        E_PARSE => "E_PARSE",
        E_NOTICE => "E_NOTICE",
        E_CORE_ERROR => "E_CORE_ERROR",
        E_CORE_WARNING => "E_CORE_WARNING",
        E_COMPILE_ERROR => "E_COMPILE_ERROR",
        E_COMPILE_WARNING => "E_COMPILE_WARNING",
        E_USER_ERROR => "E_USER_ERROR",
        E_USER_WARNING => "E_USER_WARNING",
        E_USER_NOTICE => "E_USER_NOTICE",
        E_STRICT => "E_STRICT",
        E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
        E_DEPRECATED => "E_DEPRECATED",
        E_USER_DEPRECATED => "E_USER_DEPRECATED",
        E_ALL => "E_ALL"
	];

	public $error;
	
	public function __construct(){
		$this->error = error_get_last() ?: [];
	}		

	public function check_exception(){
		return !empty($this->error);
	}

	public function get_exception_type(){
		extract($this->error);
		return $this->exception_map[$type] ?: 'unknown';
	}

	public function get_exception_message(string $append=''){
		extract($this->error);
		return "{$message} {$file} {$line} {$append}";
	}

}

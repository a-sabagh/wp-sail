<?php 

namespace SAIL\Packages\Http;

use Exception;

class Validator {

    /**
     * validator request instance
     *
     * @var SAIL\Packages\Request
     */
	protected $request;

    /**
     * message bag instance
     *
     * @var SAIL\Packages\Message
     */
	protected $errors;

	public function __construct(Request $request){
		$this->request = $request;
		$this->errors = new MessageBag();
	}


	public function errors(){
		return $this->errors;
	}

	public function request(){
		return $this->request;
	}

	protected function generate_error_key(array $validation){
		[
			'key' => $key,
			'rule' => $rule,
			'sanitize' => $sanitize,
		] = $validation;
		return "{$key}_{$rule}_{$sanitize}";
	}

	public function make(array $validations){
		if(empty($validations)){
			return;
		}
		foreach($validations as $validation){
			$rule = $validation['rule'];
			if( empty($rule) ){
				throw new Exception( __('validation rule is not set','SAIL') );
			}
			$method = "check_{$rule}";
			if( !method_exists($this,$method) ){
				throw new Exception( sprintf( __('invalid validation rule %s','SAIL'), $rule ) );
			}
			$this->$method($validation);
		}
	}

	public function check_required(array $validation){
		[
			'key' => $key,
			'label' => $label,
			'rule' => $rule,
			'sanitize' => $sanitize,
		] = $validation;
		$value = $this->request->input($key, $sanitize);
		$error_key = $this->generate_error_key($validation);
		$error_message = sprintf(__("%s is a required field","SAIL"), $label);
		$this->errors()->add_if( empty($value) , $error_key, $error_message );
	}

	public function check_required_array($validation){
		[
			'key' => $key,
			'label' => $label,
			'rule' => $rule,
			'sanitize' => $sanitize,
		] = $validation;
		$value = $this->request->input($key,$sanitize);
		$error_key = $this->generate_error_key($validation);
		$error_message = sprintf( __("%s is a required field","SAIL"), $label );
		$indexes = isset($validation['indexes']) ? $validation['indexes'] : array_keys($value);
		foreach($value as $key => $item_value){
			if(in_array($key,$indexes) && empty($item_value)){
				return $this->errors()->add( $error_key, $error_message );
			}
		}
	}

	public function check_numeric($validation){
		[
			'key' => $key,
			'label' => $label,
			'rule' => $rule,
			'sanitize' => $sanitize,
		] = $validation;
		$value = $this->request->input($key,$sanitize);
		$error_key = $this->generate_error_key($validation);
		$error_message = sprintf(__("%s is a numeric field,please enter a numeric value","SAIL"), $label);
		return $this->errors()->add_if( !is_numeric($value) , $error_key, $error_message );
	}

}

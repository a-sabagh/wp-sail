<?php

namespace WOAP\Packages;

class Request {

	const Http_Client_Error = 303;
	const Http_Server_Error = 500;
	const Http_Access_Forbiden = 403;
	const Http_Not_Found = 404;
	const Http_Move_Permanently = 301;
	const Http_Unauthorized = 401;

	public static $server_ip_keys = [
		'HTTP_CLIENT_IP',
		'HTTP_X_FORWARDED_FOR',
		'REMOTE_ADDR',
	];

	public static $sanitize_types = [
		'string',
		'integer',
		'price_format',
		'array_filter',
		'array_int',
		'email',
		'untrailingslashit',
		'trailingslashit',
		'explode_eol',
	];

	private $headers;
	private $session;
	private $ip_address;
	private $request_method;
	private $request_body;
	private $request_identifier;
	private $client_identifier;

	public function __construct(){
		$this->request_headers = getallheaders();
		$this->set_ip_address();
		$this->set_request_method($this->get_default_request_method());
		add_action('wore_route_init',[$this,'register_request_session'],10);
		foreach(self::$sanitize_types as $sanitize_type){
			add_filter("worerequest_sanitize_{$sanitize_type}",[$this,"sanitize_{$sanitize_type}"]);
		}
	}

	public function get_query_string(array $sanitize_types=[],array $defaults=[]){
		return $this->set_request_method('get')->except(null,$sanitize_types,$defaults);
	}

	public function register_request_session(){
		$this->client_identifier = $this->calculate_client_identifier();
		$this->request_identifier = $this->get_unique_request_identifier();
		$this->session = new Session($this->get_header('user-agent'),$this->get_ip_address(),$this->client_identifier,$this->request_identifier);
	}

	public function calculate_client_identifier(){
		$application_token = $this->input('woap_request_identifier','string',null);
		if(isset($application_token) || strlen($application_token) > 0){
			return $application_token;
		}
		$user_id = (int) get_current_user_id();
		if($user_id > 0){
			return $user_id;
		}
		return $this->generate_unique_client_identifier();
	}

	public function get_client_identifier(){
		return $this->client_identifier;
	}

	public function get_request_identifier(){
		return $this->request_identifier;
	}

	public function get_unique_request_identifier(){
		global $route_expression;
		return md5($route_expression);
	}

	public function generate_unique_client_identifier(){
		$server_keys = self::$server_ip_keys;
		$user_agent = $this->get_header('user-agent');
		$unique_identifier = '';
		foreach($server_keys as $key){
			$unique_identifier .= @$_SERVER[$key];
		}
		$unique_identifier .= $user_agent;
		return md5($unique_identifier);
	}

	public function set_ip_address(){
		$server_keys = self::$server_ip_keys;
		foreach($server_keys as $key){
			$server_value = isset($_SERVER[$key])? $_SERVER[$key] : '';
			if(!empty($server_value)){
				$this->ip_address = $server_value;
				break;
			}
		}
	}

	public function get_ip_address(){
		return $this->ip_address;
	}

	public function get_header($key){
		$upper_key = strtoupper($key);	
		return @$this->headers[$upper_key];
	}

	public function session(){
		return $this->session;
	}

	public function user(){
		$user = Auth::user();
		return $user;
	}

	public function set_request_method($request_method){
		$this->request_method = ('GET' == strtoupper($request_method))? 'GET' : 'POST';
		$this->request_body = ('GET' == $this->request_method)? $_GET : $_POST;
		return $this;
	}

	public function get_request_method(){
		return strtoupper($this->request_method);
	}

	public function get_default_request_method(){
		return ('GET' == $_SERVER['REQUEST_METHOD'])? 'GET' : 'POST';
	}

    public function input($key,$sanitize_type='string',$default = null){
        $value = isset($this->request_body[$key]) ? $this->request_body[$key] : $default ?? null;
		$sanitize_type = (isset($this->request_body[$key]))? $sanitize_type : 'none';
		$this->set_request_method($this->get_default_request_method());
        return $this->sanitize($value,$sanitize_type);
    }

    public function only($keys,$sanitize_types=null,$defaults=[]){
        foreach($keys as $index => $key){
            $sanitize_type = @$sanitize_types[$key] ?? 'string';
            $default = @$defaults[$key] ?? null;
            $values[$key] = $this->input($key,$sanitize_type,$default);
        }
        return $values ?? [];
    }

	public function like_only($expression,$sanitize_type='string'){
		$sanitize_types = $keys = [];
		foreach($this->request_body as $key => $value){
			if(0 !== strpos($key,$expression)){
				continue;
			}
			$keys[]=$key;
			$sanitize_types[] = $sanitize_type;
		}
		return $this->only($keys,$sanitize_types);
	}

	public function except($key = null,$sanitize_type=null,array $defaults=[]){
		if(isset($this->request_body[$key])){
			unset($this->request_body[$key]);
		}
		$keys = array_unique(array_merge(array_keys($this->request_body),array_keys($defaults)));
		return $this->only($keys,$sanitize_type,$defaults);
	}

	public function file($key){
		$file_collection =  $_FILES[$key];
		return ($file_collection['tmp_name']) ?: null;
	}

	public function validate(array $validations){
		if(empty($validations)){
			return;
		}
		foreach($validations as $validation){
			[
				'key' => $key,
				'label' => $label,
				'rule' => $rule,
				'sanitize' => $sanitize,
			] = $validation;
			$value = $this->input($key,$sanitize);
			if(!$this->validation_rules($value,$rule)){
				$error_message = $this->validation_error($rule,$label);
				$this->session()->set_error($error_message,'error');
			}
			
		}
	}

	protected function validation_error($rule,$label){
		switch($rule){
			case 'required':
				return __("{$label} is a required field","wore");
			case 'numeric':
				return __("{$label} is a numeric field,please enter a numeric value","wore");
		}
	}

	protected function validation_rules($value,string $rule="required"){
		switch($rule){
			case 'required':
				return (!empty($value));
			case 'numeric':
				return is_numeric($value);
		}
	}

    protected function sanitize($value,$sanitize_type){
		$sanitize_type = str_replace("-","_",$sanitize_type);
		return apply_filters("worerequest_sanitize_{$sanitize_type}",$value);
    }

	public function sanitize_string($value){
		return sanitize_text_field( $value );
	}

	public function sanitize_integer($value){
		return (int) str_replace(',','',$value);
	}

	public function sanitize_price_format($value){
		return (int) str_replace(',','',$value);
	}

	public function sanitize_array_filter($value){
		return array_filter($value);
	}

	public function sanitize_array_int($value){
		settype($value,'array');
		return array_map('intval',$value);
	}

	public function sanitize_email($value){
		return sanitize_email($value);
	}

	public function	sanitize_untrailingslashit($value){
		return untrailingslashit($value);
	}

	public function sanitize_trailingslashit($value){
		return trailingslashit($value);
	}

	public function sanitize_explode_eol($value){
		return explode(PHP_EOL,$value);
	}

}

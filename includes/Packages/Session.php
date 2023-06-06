<?php

namespace WOAP\Packages;

use WOAP\Resources\SessionResource;

class Session {

	private $client_identifier;
	private $request_identifier;
	private $user_agent;
	private $user_id = 0;
	private $ip_address;
	private $session;
	private $errors = [];
	private $payload = [];

	public function __construct($user_agent,string $ip_address=null,string $client_identifier,string $request_identifier=null){
		$this->user_agent = $user_agent;
		$this->client_identifier = $client_identifier ?: 'guest';
		$this->ip_address = $ip_address;
		$this->request_identifier = $request_identifier;
		$this->prepare_session_attributes($client_identifier);
		add_action('wore_request_shutdown',[$this,'update_session']);
	}

	public function get_request_identifier(){
		return $this->request_identifier;	
	}

	public function prepare_session_attributes($client_identifier){
		$resource = new SessionResource;
		$this->resource = $resource->find($client_identifier);
		if(false == $this->resource instanceof SessionResource){
			$resource->set_data(['identifier' => $this->client_identifier]);
			$this->resource = $resource;
		}
		$this->payload = $this->resource->get_payload();
		$this->errors = @$this->payload['errors'];
		$this->errors[$this->request_identifier] = [];
	}

	public function set_identifier(string $identifier){
		$this->client_identifier = $identifier;
		return $this;
	}

	public function set_user_id(int $user_id){
		$this->user_id = $user_id;
		return $this;
	}

	public function put($key,$value){
		$this->payload[$key] = $value;
		return $this;
	}

	public function pull($key,$default=null){
		return @$this->payload[$key] ?: $default;
	}

	public function fetch(){
		return $this->payload;
	}

	public function forget($key){
		unset($this->payload[$key]);
		return $this;
	}

	public function flush(){
		$this->payload = [];
		return $this;
	}

	public function set_error($message,$type='error'){
		$error = ['message' => $message,'type' => $type];
		$this->errors[$this->request_identifier][] = $error;
	}

	public function get_request_errors(){
		return @$this->errors[$this->request_identifier] ?: false;
	}

	public function get_client_errors(){
		return $this->errors;	
	}

	public function update_session(){
		$this->payload['errors'] = $this->errors;
		$this->resource->set_data([
			'identifier' => $this->client_identifier,
			'user_id' => $this->user_id,
			'updated_at'=>time(),
			'user_agent' => $this->user_agent,
			'ip_address' => $this->ip_address
		])->save();
		$this->resource->set_payload($this->payload);
	}

}

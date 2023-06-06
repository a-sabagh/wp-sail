<?php

namespace WOAP\Packages;

use Handlebars\Handlebars;

class Controller {

	public $service_container;
	public $request;
	public $repository;
	public $handlebars;

	private $process_log;
	private $process_id = 0;
	private $error_code = 0;

	public function __construct($service_container){
		$this->initialize_handlebars();
		$this->repository = wore_repository();
		$this->service_container = $service_container;		
		$this->request = wore_repository()->get(Request::class);
		add_action("wore_http_request",[$this,'package_http_post_handler']);
	}

	public function set_error_code(int $error_code){
		$this->error_code = $error_code;
		return $this;
	}

	public function get_error_code(){
		return $this->error_code;
	}

	public function set_process_id($result){
		if(is_numeric($result)){
			$this->process_id = $result; 
		}
		if($result instanceof WP_Error){
			$error = $result;
			$this->set_error_code($error->get_error_code());
			$this->set_process_log($error->get_error_message());
		}
		return $this;
	}

	public function get_process_id(){
		return $process_id;
	}

	public function set_process_log(string $log){
		$this->process_log[] = $log;
		return $this;
	}

	public function get_process_log(){
		return $this->process_log;
	}

	public function initialize_handlebars(){
		$this->handlebars = new Handlebars;
		$this->handlebars->addHelper("repeatArg", function($template, $context, $args, $source){
			$counter = $context->get($args) ?: 0;
			$output = '';
			for($i=0;$i<$counter;$i++){
				$output .= $template->render($context);
			}
			return $output ?? '';
		});	
	}

	public function package_http_post_handler(){
		global $endpoint,$module,$action;	
		$nonce_key = "{$endpoint}_{$module}_{$action}_nonce_key";
		if(empty($this->request->input($nonce_key))){
			return;
		}
		$nonce_value = "{$endpoint}-{$module}-{$action}-nonce-value";
		$nonce_value_posted = $this->request->input($nonce_key,'none');
		$verify_nonce = wp_verify_nonce($nonce_value_posted,$nonce_value);
		$request_type = $this->request->input('request_type');
		$method_name = strtolower("response_{$module}_{$action}{$request_type}");
		if($verify_nonce && method_exists($this,$method_name)){
			$this->$method_name();
		}
	}

}

<?php

namespace SAIL\Packages\Contracts;

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
		$this->repository = sail_repository();
		$this->service_container = $service_container;		
		$this->request = sail_repository()->get(Request::class);
		add_action("sail_http_request",[$this,'package_http_post_handler']);
	}

	public function get_response_view(string $endpoint,string $slug){
		$resolved_slug = str_replace('.', '/', $slug);
		return trailingslashit(SAIL_VIEW) 
			. trailingslashit( strtolower( $endpoint ) )
			. "{$resolved_slug}.php";
	}

	public function response(string $endpoint,string $slug, array $args){
		extract($args);
		require $this->get_response_view($endpoint,$slug);
	}

	public function redirect(){
		return new RedirectResponse($this->request, $this->request->session());
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
		$verify_nonce = sail_verify_nonce($endpoint,$module,$action,$this->request);
		$request_type = $this->request->input('request_type');
		$method_name = strtolower("response_{$module}_{$action}{$request_type}");
		if($verify_nonce && method_exists($this,$method_name)){
			$this->$method_name();
		}
	}

}

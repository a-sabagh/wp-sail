<?php

namespace SAIL\Packages\Providers;

use LoggerWp\Exception\LogerException;

class RoutProvider {

	public $service_container;
	public $route_mapping;
	public $request_rule_arr;
	
	public function __construct($service_container,$route_mapping,$request_rule_arr=null){
		$this->service_container = $service_container;
		$this->route_mapping = $route_mapping;
		$this->request_rule_arr = $request_rule_arr;
		$this->add_rewrite_rule();
		add_action('template_redirect', [$this,'route_init']);
	}

	public function get_route_mapping(){
		return apply_filters('sail_route_collection',$this->route_mapping);
	}

    public function add_rewrite_rule() {
		$route_mapping = $this->get_route_mapping();
		foreach($route_mapping as $slug => $route_item){
			add_rewrite_rule("^{$slug}/([^/]*)/?([^/]*)/?([^/]*)/?$", 'index.php?sail_class=$matches[1]&sail_action=$matches[2]&sail_params=$matches[3]&sail_endpoint=' . $slug, "top");
			add_rewrite_tag("%sail_class%", "([^/]*)");
			add_rewrite_tag("%sail_action%", "([^/]*)");
			add_rewrite_tag("%sail_params%", "([^/]*)");
			add_rewrite_tag("%sail_endpoint%", "([^/]*)");
		}
    }

	public function route_init(){
		global $endpoint;
        $endpoint = get_query_var("sail_endpoint");
		if(empty($endpoint)){
			unset($endpoint);
			return;
		}
		global $module,$action,$route_type,$route_expression,$request_params;
		$route_mapping = $this->get_route_mapping();
		$namespace = $route_mapping[$endpoint]['namespace'];
		$route_type = $route_mapping[$endpoint]['type'] ?? 'api';
        $module = get_query_var("sail_class");
        $action = get_query_var("sail_action") ?: 'index';
        $params = get_query_var("sail_params");
		$endpoint_tolower = strtolower($endpoint);
		$module_tolower = strtolower($module);
		$action_tolower = strtolower($action);
		parse_str($params,$request_params);
		$route_expression = "{$endpoint_tolower}/{$module_tolower}/{$action_tolower}";
		do_action('sail_application_bootstrap', $route_expression );
		do_action('sail_session_start', $route_expression );
		try{
			do_action("sail_route_init",$endpoint,$module,$action);
			do_action("sail_authentication_{$endpoint_tolower}");
			do_action("sail_authentication_{$endpoint_tolower}_{$module_tolower}");
			do_action("sail_authentication_{$endpoint_tolower}_{$module_tolower}_{$action_tolower}");
			do_action("sail_http_request");
			do_action("sail_http_request_{$endpoint_tolower}");
			do_action("sail_http_request_{$endpoint_tolower}_{$module_tolower}");
			do_action("sail_http_request_{$endpoint_tolower}_{$module_tolower}_{$action_tolower}");
			$namespace_class = "{$namespace}\\{$module}";
			$service = $this->service_container->get($namespace_class);
			$service->logger->setChannel($endpoint_tolower);
			$method_name = $route_type . "_response_body";
			if(!is_object($service) || !method_exists($service,$action)){
				throw new LogerException(
					sprintf(
						__("Woocommerce Reserve invalid routing combination %s/%s/%s","SAIL"),
						$endpoint,$module,$action
					)
					,404
				);
			}
			do_action('sail_request_start',$action,$request_params);
			$this->$method_name($service,$action,$request_params);
		}catch(LogerException $exception){
			do_action('sail_exception_handling',$exception);
			$method_name = $route_type . "_error_handling";
			$this->$method_name($exception);
		}finally{
			do_action('sail_session_close');
			do_action('sail_application_shutdown');
		}
		exit;
	}

	public function web_error_handling(LogerException $exception){
		do_action('sail_exception_handling',$exception);
		$error_message = $exception->getMessage();
		wp_die($error_message);
	}

	public function content_error_handling(LogerException $exception){
		do_action('sail_exception_handling',$exception);
		$error_message = $exception->getMessage();
		add_filter('the_content',function($content) use ($error_message){
			return apply_filters('sail_content_response_body',$error_message);	
		});
	}

	public function api_error_handling(LogerException $exception){
		do_action('sail_exception_handling',$exception);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
		echo json_encode(
			[
				'status' => false,
				'data' => [
					'code' => $exception->getCode(),
					'errors' => [
						[
							'type' => 'error',
							'message' => $exception->getMessage(),
						],
					],
				],
			]
		);		
	}

	public function api_response_body($service,$action,$param){
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
		$result = $service->$action($param);
		$code = isset($result['code'])? $result['code'] : 200;
		echo json_encode($result);
	}

	public function web_response_body($service,$action,$param){
		if(!is_object($service) || !method_exists($service,$action)){
			http_response_code(404);
			die('404 Not Found');
		}
		ob_start();
		$service->$action($param);
		$output = apply_filters('sail_route_web_response_body',ob_get_clean());
		echo $output;
	}

	public function content_response_body($service,$action,$param){
		do_action("sail_content_routing_start",$service,$action,$param);
		$output = $service->$action();
		add_filter('the_content', function($content) use ($output){
			return apply_filters('sail_content_response_body',$output);	
		});
	}

}

<?php

namespace WOAP\Packages;

class Router {

	public $service_container;
	public $route_mapping;
	public $request_rule_arr;
	
	public function __construct($service_container,$route_mapping,$request_rule_arr=null){
		$this->service_container = $service_container;
		$this->route_mapping = $route_mapping;
		$this->request_rule_arr = $request_rule_arr;
        add_action("init", array($this, "add_rewrite_rule"));
		add_action('template_redirect', [$this,'route_init']);
	}

    public function add_rewrite_rule() {
		$route_mapping = apply_filters('wore_route_collection',$this->route_mapping);
		foreach($route_mapping as $slug => $route_item){
			add_rewrite_rule("^{$slug}/([^/]*)/?([^/]*)/?([^/]*)/?$", 'index.php?wore_class=$matches[1]&wore_action=$matches[2]&wore_params=$matches[3]&wore_endpoint=' . $slug, "top");
			add_rewrite_tag("%wore_class%", "([^/]*)");
			add_rewrite_tag("%wore_action%", "([^/]*)");
			add_rewrite_tag("%wore_params%", "([^/]*)");
			add_rewrite_tag("%wore_endpoint%", "([^/]*)");
		}
    }

	public function route_init(){
		global $endpoint;
        $endpoint = get_query_var("wore_endpoint");
		if(empty($endpoint)){
			unset($endpoint);
			return;
		}
		global $module,$action,$route_type,$route_expression,$request_params;
		$namespace = $this->route_mapping[$endpoint]['namespace'];
		$route_type = $this->route_mapping[$endpoint]['type'] ?? 'api';
        $module = get_query_var("wore_class");
        $action = get_query_var("wore_action") ?: 'index';
        $params = get_query_var("wore_params");
		$endpoint_tolower = strtolower($endpoint);
		$module_tolower = strtolower($module);
		$action_tolower = strtolower($action);
		parse_str($params,$request_params);
		$route_expression = "{$endpoint_tolower}/{$module_tolower}/{$action_tolower}";
		do_action("wore_route_init",$endpoint,$module,$action);
		do_action("wore_authentication_{$endpoint_tolower}");
		do_action("wore_authentication_{$endpoint_tolower}_{$module_tolower}");
		do_action("wore_authentication_{$endpoint_tolower}_{$module_tolower}_{$action_tolower}");
		do_action("wore_http_request");
		do_action("wore_http_request_{$endpoint_tolower}");
		$namespace_class = "{$namespace}\\{$module}";
		$object = $this->service_container->get($namespace_class);
		$method_name = $route_type . "_response_body";
		do_action('wore_request_start',$action,$request_params);
		$this->$method_name($object,$action,$request_params);
	}

	public function api_response_body($object,$action,$param){
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
		if(!is_object($object) || !method_exists($object,$action)){
			http_response_code(404);
			echo json_encode([
					'status' => false,
					'message' => '404 Not Found',
				]);		
			die();	
		}	
		$result = $object->$action($param);
		$status_code = isset($result['code'])? $result['code'] : 200;
		wp_send_json($result,$status_code);
	}

	public function web_response_body($object,$action,$param){
		if(!is_object($object) || !method_exists($object,$action)){
			http_response_code(404);
			die('404 Not Found');
		}
		ob_start();
		$object->$action($param);
		do_action('wore_route_web_response_body');
		$output = ob_get_clean();
		echo $output;
		exit;
	}

	public function content_response_body($object,$action,$param){
		do_action("wore_content_routing_start",$object,$action,$param);
		add_filter('the_content',[$object,$action]);	
	}

	public function __destruct(){
		global $endpoint;
		if(empty($endpoint)){
			return;
		}
		do_action('wore_request_shutdown');
	}

}

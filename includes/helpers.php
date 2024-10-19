<?php

function woocommerce_application_init(){
	global $woocommerce_application_init;
	if($woocommerce_application_init instanceof WOAP\Init){
		return;
	}
	require_once trailingslashit(__DIR__) . "Init.php";
	$woocommerce_application_init = new WOAP\Init(1.0, 'woap', 'WOAPApi');
	do_action('woocommerce_application_loaded',$woocommerce_application_init);
}

function woap_create_nonce(string $endpoint,string $module,string $action){
	ob_start();
	$nonce_key = "{$endpoint}_{$module}_{$action}_nonce_key";
	$nonce_value = "{$endpoint}-{$module}-{$action}-nonce-value";
	wp_nonce_field($nonce_value,$nonce_key);
	return ob_get_clean();
}

function woap_response_view(string $absolute_path,array $arguments,bool $output_type=false){
	extract($arguments);
	ob_start();
	require $absolute_path;
	return ($output_type)? ob_get_flush() : ob_get_clean();
}

function woap_implode_eol($arr){
	return implode(PHP_EOL,$arr);
}

function woap_explode_eol($string){
	return explde(PHP_EOL,$string);
}

function woap_nested_access($data,...$keys){
	if(!isset($data) || empty($keys)){
		return;
	}
	foreach($keys as $key){
		if(!isset($data[$key])){
			return;
		}
		$data = $data[$key];
	}
	return $data;
}

function woap_repository(){
    global $wore_repository;
    $repository = WPLS\Packages\Repository::class;
    if(false == $wore_repository instanceof $repository){
        $wore_repository = new $repository;
    }
    return $wore_repository;
}

function woap_get_request_uri(string $endpoint=null,string $module=null,string $action=null,array $query_args=[]){
	$request_url = trailingslashit(home_url("{$endpoint}/{$module}/{$action}"));
	return add_query_arg($query_args,$request_url);
}

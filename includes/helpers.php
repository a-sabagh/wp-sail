<?php

use SAIL\Init;
use SAIL\Packages\Request;
use SAIL\Packages\Repository;

function wp_sail_plugin_init(){
	global $wc_reserve_hotel_init;
	if($wc_reserve_hotel_init instanceof Init){
		return;
	}
	require_once trailingslashit(__DIR__) . "Init.php";
	$wc_reserve_hotel_init = new Init(1.0, 'SAIL', 'SAILApi');
	do_action('woocommerce_reserve_loded',$wc_reserve_hotel_init);
}

function sail_service_container(){
	global $wc_reserve_hotel_init;
	if(false == $wc_reserve_hotel_init instanceof Init){
		throw new Exception(__('Service Container Error','SAIL'));
	}
	return $wc_reserve_hotel_init->service_container;
}

function sail_response_view(string $absolute_path,array $arguments,bool $output_type=false){
	extract($arguments);
	ob_start();
	require $absolute_path;
	return ($output_type)? ob_get_flush() : ob_get_clean();
}

function sail_is_hotel_category(){
	if(!is_product_category()){
		return;
	}
	$term = get_queried_object();
	$term_id = $term->term_id;
	$parent_id = get_woocommerce_term_meta(
		$term_id,
		HotelCategoryModel::termmeta,
		true
	);
	return $parent_id > 0;
}

function sail_repository(){
	global $woap_repository;
	if(false == $woap_repository instanceof Repository){
		$woap_repository = new Repository;
	}
	return $woap_repository;
}

function sail_amount_filter($amount){
	$amount = (int) $amount;
	return max(0,$amount);
}

function sail_price_format(int $price){
	if(0 === $price){
		return false;
	}
	$price = (float) $price;	
	$decimal = wc_get_price_decimals();
	$decimal_seprator = wc_get_price_decimal_separator();
	$thousand_seprator = wc_get_price_thousand_separator();
	return number_format($price,$decimal,$decimal_seprator,$thousand_seprator);
}

function sail_custom_excerpt(int $count,string $content){
	$output = $content;
	$output = strip_tags($output);
	$output = mb_substr($output , 0 , $count);
	if(strlen($content) > strlen($output)){
		$output = mb_substr($output , 0 , mb_strrpos($output, " "));
		$output .= "...";
	}
	return $output;
}

function sail_insert_array(&$array, $position, $insert){
    if (is_int($position)) {
        array_splice($array, $position, 0, $insert);
    } else {
        $pos   = array_search($position, array_keys($array));
        $array = array_merge(
            array_slice($array, 0, $pos),
            $insert,
            array_slice($array, $pos)
        );
    }
}

function sail_get_request_uri(string $endpoint=null,string $module=null,string $action=null,array $query_args=[]){
	$request_url = trailingslashit(home_url("{$endpoint}/{$module}/{$action}"));
	return add_query_arg($query_args,$request_url);
}

function sail_implode_eol($arr){
	return implode(PHP_EOL,$arr);
}

function sail_explode_eol($string){
	return explde(PHP_EOL,$string);
}

function sail_nested_access($data,...$keys){
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

function sail_nested_access_e($data,...$keys){
	echo sail_nested_access($data,...$keys);
}

function sail_get_string_nullable($value){
	return 0 === strlen($value) ? $value : null;
}

function sail_check_string_nullable($value){
	return 0 === strlen($value);
}

function sail_nested_map_default(array &$receive,array $default){
	if(empty($default)){
		return $receive;
	}
	foreach($default as $key => $value){
		$receive[$key] = (is_array($value) && !empty($value))? 
			sail_nested_map_default($receive[$key],$value)
			: (isset($receive[$key])? $receive[$key] : $value);
	}
	return $receive;
}

function sail_create_nonce(string $endpoint,string $module,string $action){
	ob_start();
	$nonce_key = "{$endpoint}_{$module}_{$action}_nonce_key";
	$nonce_value = "{$endpoint}-{$module}-{$action}-nonce-value";
	wp_nonce_field($nonce_value,$nonce_key);
	return ob_get_clean();
}

function sail_create_nonce_field(string $endpoint,string $module,string $action){
	echo sail_create_nonce($endpoint,$module,$action);
}

function sail_create_nonce_field_e(string $endpoint,string $module,string $action){
	echo sail_create_nonce_field($endpoint,$module,$action);
}

function sail_verify_nonce(string $endpoint,string $module,string $action,Request $request){
	$nonce_key = "{$endpoint}_{$module}_{$action}_nonce_key";
	$request_nonce_value = $request->input($nonce_key,'none');
	if(empty($request_nonce_value)){
		return false;
	}
	$nonce_value = "{$endpoint}-{$module}-{$action}-nonce-value";
	return wp_verify_nonce($request_nonce_value,$nonce_value);
}

function sail_generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

function sail_generate_random_title(...$keys){
	$salt = implode('-',func_get_args());
	$random_string = sail_generate_random_string(5);
	$microtime = microtime();
	return  "{$salt}-{$random_string}-{$microtime}";
}

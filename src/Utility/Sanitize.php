<?php

namespace WPSail\Utility;

use Exception;

class Sanitize {

	/**
	 * Sanitize mapped values in a data array.
	 *
	 * @param array $data The data to sanitize.
	 * @param array $sanitize_map Field names mapped to sanitizer types.
	 * @return array
	 * @throws Exception If a sanitizer type is not defined.
	 */
    public static function make( &$data, $sanitize_map ){

		if( empty($data) || empty($sanitize_map) ){
			return $data;
		}

		foreach( $sanitize_map as $key => $type ){
			if( !isset($data[$key]) ){
				continue;
			}
			$type = str_replace("-","_",$type);
			if( !method_exists(__CLASS__, $type) ){
				throw new Exception( sprintf( __('sanitize type %s is undefined on %s', 'wpsail'), $type, __CLASS__ ) );
			}
			$data[$key] = apply_filters( "wpsail_sanitize_type_{$key}_{$type}", self::$type( $data[$key] ) );
		}
		return $data;
    }

	/**
	 * Sanitize a text value.
	 *
	 * @param string $value The value to sanitize.
	 * @return string
	 */
	public static function string($value){
		return sanitize_text_field( $value );
	}

	/**
	 * Convert a formatted numeric value to an integer.
	 *
	 * @param mixed $value The value to convert.
	 * @return int
	 */
	public static function integer($value){
		return (int) str_replace(',','',$value);
	}

	/**
	 * Convert a value to a boolean.
	 *
	 * @param mixed $value The value to convert.
	 * @return bool
	 */
	public static function boolean($value){
		return (bool) $value;
	}

	/**
	 * Convert a formatted price to an integer when it is not empty.
	 *
	 * @param mixed $value The price to convert.
	 * @return int|null
	 */
	public static function price_format($value){
		if(wpsail_check_string_nullable($value)){
			return;
		}
		return (int) str_replace(',', '', $value);
	}

	/**
	 * Remove empty values from an array.
	 *
	 * @param array $value The array to filter.
	 * @return array
	 */
	public static function array_filter($value){
		return array_filter($value);
	}

	/**
	 * Convert each value in an array to an integer.
	 *
	 * @param mixed $value The value to normalize as an integer array.
	 * @return int[]
	 */
	public static function array_integer($value){
		settype($value,'array');
		return array_map('intval',$value);
	}

	/**
	 * Sanitize an email address.
	 *
	 * @param string $value The email address to sanitize.
	 * @return string
	 */
	public static function email($value){
		return sanitize_email($value);
	}

	/**
	 * Remove trailing forward slashes and backslashes from a string.
	 *
	 * @param string $value The value to normalize.
	 * @return string
	 */
	public static function	untrailingslashit($value){
		return untrailingslashit($value);
	}

	/**
	 * Ensure a string ends with a forward slash.
	 *
	 * @param string $value The value to normalize.
	 * @return string
	 */
	public static function trailingslashit($value){
		return trailingslashit($value);
	}

	/**
	 * Split a string at platform-specific line endings.
	 *
	 * @param string $value The value to split.
	 * @return string[]
	 */
	public static function explode_eol($value){
		return explode(PHP_EOL,$value);
	}

	/**
	 * Remove backslashes from a string.
	 *
	 * @param string $value The value to normalize.
	 * @return string
	 */
	public static function stripslashes($value){
		return stripslashes($value);
	}

	/**
	 * Sanitize a value as a URL-friendly slug.
	 *
	 * @param string $value The value to sanitize.
	 * @return string
	 */
	public static function slug($value){
		return sanitize_title_with_dashes($value);
	}

	/**
	 * Sanitize a title value.
	 *
	 * @param string $value The value to sanitize.
	 * @return string
	 */
	public static function title($value){
		return sanitize_title($value);
	}

	/**
	 * Return a value without sanitizing it.
	 *
	 * @param mixed $value The value to return.
	 * @return mixed
	 */
	public static function none($value){
		return $value;
	}

}

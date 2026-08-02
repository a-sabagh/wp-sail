<?php

function wpsail_get_string_nullable($value){
	return 0 === strlen($value) ? $value : null;
}

function wpsail_check_string_nullable($value){
	return 0 === strlen($value);
}

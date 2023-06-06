<?php

namespace WOAP\Repositories;

use WOAP\Packages\QueryBuilder;
use WOAP\Controllers\Action\User as UserAction;

class User {

	public function get_user_id_by_meta(string $meta_key,string $meta_value){
		$query_builder = new QueryBuilder;
		$user_rows = $query_builder->table('usermeta')->select('user_id')->where('meta_key','=',$meta_key)->where('meta_value','LIKE',$meta_value)->get();
		if(empty($user_rows)){
			return null;
		}
		$user_row = current($user_rows);
		return $user_row['user_id'];
	}

	public function get_user_id_by_token(string $token){
		$query_builder = new QueryBuilder;
		$meta_key = UserAction::token_identifier;
		$user_rows = $query_builder->table('usermeta')->select('user_id')->where('meta_key','=',$meta_key)->where('meta_value','=',$token)->get();
		if(empty($user_rows)){
			return null;
		}
		$user_row = current($user_rows);
		return $user_row['user_id'];
	}

}

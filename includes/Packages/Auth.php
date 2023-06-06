<?php 

namespace WOAP\Packages;

use WOAP\Resources\UserResource as User;

class Auth {

	public static function user(){
		if(!is_user_logged_in()){
			return null;
		}
		$user_id = get_current_user_id();
		return new User($user_id);
	}

}

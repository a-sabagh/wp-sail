<?php

namespace SAIL\Resources;

use SAIL\Models\Session;
use SAIL\Traits\Timestamp as TimestampTrait;

class SessionResource extends Session {

	use TimestampTrait;

	public function get_payload(){
		$payload = $this->get_data('payload');
		return (empty($payload))? [] : maybe_unserialize($payload);
	}

	public function set_payload($payload=[]){
		return $this->set_data(['payload' => maybe_serialize($payload)]);
	}

}

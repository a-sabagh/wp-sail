<?php

namespace SAIL\Traits;

use WC_DateTime;

trait Timestamp {

	public function get_date_created_at(){
		return new WC_DateTime($this->get_data('created_at'));
	}

	public function get_date_updated_at(){
		return new WC_DateTime($this->get_data('updated_at'));
	}

}

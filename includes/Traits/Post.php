<?php

namespace SAIL\Traits;

use WC_DateTime;

trait Post {

	public function get_post_type(){
		return get_post_type( $this->get_id() );
	}

	public function get_permalink(){
		return get_the_permalink( $this->get_id() );
	}

	public function get_date_created_at(){
		$date_formatted = get_the_date( 'Y-m-d h:i:s',$this->get_id() );
		return new WC_DateTime( $date_formatted );
	}

	public function get_date_updated_at(){
		$date_formatted = get_the_modified_time( 'Y-m-d h:i:s',$this->get_id() );
		return new WC_DateTime( $date_formatted );
	}

}

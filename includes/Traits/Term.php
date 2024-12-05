<?php

namespace SAIL\Traits;

trait Term {

	public function get_permalink(){
		return get_term_link( $this->get_id() );
	}

}

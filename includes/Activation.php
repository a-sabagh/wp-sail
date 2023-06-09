<?php 

namespace WOAP;

use WOAP\Packages\TableProvider;

class Activation {

	public function __construct(){
		$this->boot_tables_provider();
	}

    public function boot_tables_provider(){
		$tables = array(
			#tables here
		);
        require_once trailingslashit(__DIR__) . "Packages/TableProvider.php";
        new TableProvider($tables);
    }
}

new Activation;

<?php 

namespace SAIL;

use SAIL\Packages\Providers\TableProvider;

class Activation {

	public function __construct(){
		$this->boot_tables_provider();
	}

    public function boot_tables_provider(){
		$tables = [];
        require_once trailingslashit(__DIR__) . "Packages/Providers/TableProvider.php";
        new TableProvider($tables);
    }
}

new Activation;

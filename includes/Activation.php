<?php 

namespace WOAP;

use WOAP\Packages\TableProvider;

class Activation {

	public function __construct(){
		$this->boot_tables_provider();
	}

    public function boot_tables_provider(){
		$tables = array(
			Tables\Messages::class => trailingslashit(__DIR__) . "Tables/Messages.php",
		);
        require_once trailingslashit(__DIR__) . "Packages/TableProvider.php";
        new TableProvider($tables);
    }
}

new Activation;

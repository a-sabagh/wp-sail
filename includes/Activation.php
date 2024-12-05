<?php 

namespace SAIL;

use SAIL\Packages\Providers\TableProvider;

class Activation {

	public function __construct(){
		$this->boot_table_provider();
	}

    public function boot_table_provider(){
		new TableProvider([
            Tables\Session::class => trailingslashit(__DIR__) . "Tables/Session.php",
		]);
    }
}

new Activation;

<?php 

namespace WOAP\Packages;

class TableProvider {
    
    public $tables;

    public function __construct($tables){
        $this->tables = $tables;
        require_once (trailingslashit(ABSPATH) . 'wp-admin/includes/upgrade.php');
        register_activation_hook(WOAP_FILE, array($this,"install"));
        register_uninstall_hook(WOAP_FILE, array(__CLASS__,"uninstall"));
    }

    public function install() {
        $tables = $this->tables;
        foreach($tables as $class => $path){
            require_once $path;
            $table_object = new $class;
            $table_object->up();
        }
    }

    public static function uninstall(){
        $reverse_tables = array_reverse($this->tables);
        foreach($reverse_tables as $class => $path){
            require_once $path;
            $table_object = new $class;
            $table_object->down();
        }
    }

}

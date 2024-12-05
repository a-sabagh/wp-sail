<?php 

namespace SAIL\Tables;

class Session {

    const table = 'sail_session';

    public $wpdb;

    function __construct(){
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function up(){
        $table = $this->wpdb->prefix . self::table;
        $sql = "CREATE TABLE IF NOT EXISTS {$table} ("
                . "id BIGINT(20) NOT NULL AUTO_INCREMENT,"
                . "identifier VARCHAR(60) NOT NULL,"
                . "user_id BIGINT(20) DEFAULT 0,"
                . "ip_address VARCHAR(15),"
                . "user_agent VARCHAR(255),"
                . "request_endpoint VARCHAR(255),"
                . "payload longtext,"
				. "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,"
				. "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,"
                . "PRIMARY KEY (id)"
                . ")"
                . "CHARACTER SET utf8 "
                . "COLLATE utf8_general_ci";
        dbDelta($sql);
    }

}

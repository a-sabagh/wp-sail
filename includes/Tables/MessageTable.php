<?php

namespace WOAP\Tables;

class Messages {

    public $wpdb;

    const table = "woap_messages";

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function up() {
        $messages = $this->wpdb->prefix . self::table;
        $sql = "CREATE TABLE IF NOT EXISTS {$messages} ("
                . "id BIGINT(20) NOT NULL AUTO_INCREMENT, "
                . "content TEXT, "
                . "updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(), "
                . "created_at TIMESTAMP NOT NULL, "
                . "PRIMARY KEY (id) "
                . ")"
                . "CHARACTER SET utf8 "
                . "COLLATE utf8_general_ci";
        dbDelta($sql);
    }

}

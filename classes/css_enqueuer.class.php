<?php

namespace baobab;

require_once "enqueuer.class.php";

class CssEnqueuer extends Enqueuer {

    private $_files = array();

    private static $_instance = null;

    public static function getInstance() {
        if( ! self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function addFile($file, $name="" ) {
        $name = $name ? $name : $file;
        $this->_files[$name]=$file;
        parent::add($name, array() );
    }

    public function flush() {
        foreach( $this->getAll() as $r ) {
            echo '<link href="'. $this->_files[$r] . '" media="all" rel="stylesheet" type="text/css">' . "\n";
        }
    }
}
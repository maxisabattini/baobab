<?php

namespace baobab;

class Session {

    protected static $_instance;
    protected $_data;
    protected $_id;

    private function __construct() {
        session_start();
        $this->_id = session_id();
        $this->_data = &$_SESSION;
    }
    
    public static function getInstance() {
        if(!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function get($var, $default=null) {            
        $value = isset($this->_data[$var]) ? $this->_data[$var] : null;
        return $value ? $value : $default;
    }
    
    public function set($var, $value) {
        $this->_data[$var]=$value;
    }

    public function close() {
        session_write_close();
        session_destroy();
    }

    public function drop($var) {
        unset($this->_data[$var]);
    }

    public function id() {
        return $this->_id;
    }
}
<?php

namespace baobab;

class Session {

	protected static $instance;
	protected $data;

    private function __construct() {
        session_name("global");
        @session_start();
        $this->data = &$_SESSION;
    }
    
    public static function getInstance() {
        if(!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($var, $default=null) {            
        $value = isset($this->data[$var]) ? $this->data[$var] : null;                
        $object = @unserialize($value);
        if( $object ) {
            $value = $object;
        }                
        return $value ? $value : $default;
    }
    
    public function set($var, $value) {
        if (is_object($value) || is_array($value)) {
            $value = serialize($value);
        }
        $this->data[$var]=$value;
    }
    
    public function drop($var) {
        unset($this->data[$var]);
    }
}


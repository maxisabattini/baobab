<?php

namespace baobab;

class Cache {

	protected static $_instance;

    protected $_link;
    protected $_isEnabled=false;

    private function __construct() {
        if(class_exists('Memcache')){
            $this->_link = new \Memcache;
            if(! $this->_link->connect('localhost', 11211) ) {
                Log::WARN("Can not connect to MEMCACHE service");
            } else {
                $this->_isEnabled = true;
            }
        } else {
            Log::WARN("No exist MEMCACHE class");
        }
    }
    
    public static function getInstance() {
        if(!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function get($var, $default=null, $duration=600 ) {

        if(! $this->_isEnabled) {
            $result=$default;
            if(is_callable($default)) {
                $result = $default( $var );
            }
            return $result;
        }

        $result = $this->_link->get( $var );
        if(!$result) {
            $result=&$default;
            if(is_callable($default)) {
                $result = $default( $var );
                $this->set( $var, $result, $duration );
            }
        }
        return $result;
    }
    
    public function set($var, $value, $duration=600 ) {
        if($this->_isEnabled) {
            $this->_link->set($var, $value, false, $duration);
        }
    }
    
    public function drop($var) {
        if($this->_isEnabled) {
            $this->_link->delete($var);
        }
    }
}


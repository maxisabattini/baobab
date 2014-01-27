<?php

namespace baobab;

class Cache {

    protected static $_instance;

    protected $_link;
    protected $_isEnabled=false;

    private function __construct() {
        if(class_exists('Memcache')){
            try {
                $this->_link = new \Memcache;
                $this->_link->connect('localhost', 11211);
                $this->_isEnabled = true;
            } catch(\Exception $e){
                Log::WARN("Can not connect to MEMCACHE service");
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
    
    public function has() {
        return !!$this->get($key);
    }
    
    public function get($key, $callable=null, $duration=600 ) {
    
        $result = null;
        
        if( $this->_isEnabled ) {
            $result = $this->_link->get( $key );
        }
        
        if( $result ) {
            return $result;
        }
        
        if(is_callable($callable)) {
            $result = $callable( $key );
            if( ! is_null($result) ) {
                $this->set( $key, $result, $duration );            
            }
        }
    
        return $result;
    }
    
    public function set($key, $value, $duration=600 ) {
        if($this->_isEnabled) {
            $this->_link->set($key, $value, false, $duration);
        }
    }
    
    public function drop($key) {
        if($this->_isEnabled) {
            $this->_link->delete($key);
        }
    }

    public function isEnabled() {
        return  $this->_isEnabled;
    }
}


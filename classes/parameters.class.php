<?php

namespace baobab;

require_once "log.class.php";

class Parameters implements \ArrayAccess {

    private $_container = null;

    public function __construct($default=array()){
        $this->_container= is_array( $default ) ? $default : array();
    }

    /*
     * Array Access
    */

    public function offsetSet($offset, $value) {
        $this->_container[$offset] = $value;
    }

    public function offsetExists($offset) {
        return isset($this->_container[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->_container[$offset]);
    }

    public function offsetGet($offset) {
        if(!isset($this->_container[$offset]) ) {
            Log::warn("Param not set: " . $offset);
            return null;
        } else {
            return $this->_container[$offset];
        }
    }

    /*
     * Object Access
    */

    public function __get($key){
        return $this->offsetGet($key);
    }

    public function __set($key, $value){
        $this->offsetSet($key, $value);
    }

    /*
     * Common
    */

    public function merge( $input ){
        if( is_object($input) ) {
            $this->_container=array_merge($this->_container, (array) $input);
        }
        if(is_array($input)) {
            $this->_container=array_merge($this->_container, $input);
        }
    }

    public function exists(){}

    public function toArray( $recursive = false ){
        return $this->_container;
    }

    public function toObject( $recursive = false ){
        return (object) $this->_container;
    }

    public function toString($format = 'JSON'){
        //TODO:
    }
}


<?php

namespace baobab;

require_once "log.class.php";

class Request {

    public function isPost() {
        return $this->_method=="post";
    }

    public function isGet() {
        return $this->_method=="get";
    }

    public function isPut() {
        return $this->_method=="put";
    }

    public function isDelete() {
        return $this->_method=="delete";
    }
    
    public function isPatch() {
        return $this->_method=="patch";
    }    

    public function isSecure(){
        if ( isset($_SERVER['HTTPS']) ) {
            if ( 'on' == strtolower($_SERVER['HTTPS']) )
                return true;
            if ( '1' == $_SERVER['HTTPS'] )
                return true;
        } elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
            return true;
        }
        return false;
    }

    public function isAjax() {
        return $_isAjax;
    }

    public function getMethod(){
        return strtoupper($this->_method);
    }

    public function getVar($name) {
        return isset($_REQUEST[$name])?$_REQUEST[$name]:null;
    }

    public function getHeader($name) {      
        return isset($this->_headers[$name])?$this->_headers[$name]:null;   
    }

    public function hasFiles(){
        //TODO:
        return false;
    }

    public function getFiles(){
        //TODO:
        return false;
    }

    public function getServerAddress() {
        //TODO:
        return false;   
    }

    public function getClientAddress() {
        //TODO:
        return false;   
    }

    public function getAcceptableContent() {
        //TODO:
        return false;   
    }

    public static function getInstance() {
        if(!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    protected static $_instance;

    private $_method='';
    private $_isAjax=false;
    private $_headers=array();

    private function __construct() {

        //Method
        $this->_method = strtolower($_SERVER['REQUEST_METHOD']);
        $this->_isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        //Headers       
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $_key=str_replace('_', ' ', substr($key, 5));
                $this->_headers[$_key]=$value;
            }
        }        
    }
}
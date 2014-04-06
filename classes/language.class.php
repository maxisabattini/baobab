<?php

namespace baobab;

class Language extends Config {

    protected $_code;

    public function __construct( $code, $path) {
        parent::__construct("language_$code");
        $this->_code = $code;

        if( !is_array($path) ){
            $path=array($path);
        }

        foreach($path as $dir) {
            $this->loadFile("$dir/$code.php");
        }
    }
    
    public function get($key, $placeholders=array()) {        
        
        if(!$this->has($key)){
            return $key;
        }

        $result=parent::get($key,$key);
        if($placeholders){
            $result = vsprintf($string, $args );
        }

        return $result; 
    }

    public function getCode(){
        return $this->_code;
    }
}

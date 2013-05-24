<?php

namespace baobab;

class Log {

    const NONE = 0;    
    const ERROR = 1;
    const WARN = 2;
    const INFO = 3;
    const DEBUG = 4;
    const ALL = 100;
    
    public static $level = 0;	
    
    public static function error($message) {
        if(self::$level > 0) {
            error_log($message);
        }
    }
    
    public static function warn($message) {
        if(self::$level > 1 ){
            error_log($message);
        }
    }
    
    public static function info($message) {
        if( self::$level > 2 ){
            error_log($message);
        }
    }
    
    public static function debug($message) {
        if( self::$level > 2 ){
            error_log($message);
        }        
    }    
}



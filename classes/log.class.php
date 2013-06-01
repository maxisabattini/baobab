<?php

namespace baobab;

class Log {

    const NONE = 0;    
    const ERROR = 1;
    const WARN = 2;
    const INFO = 3;
    const DEBUG = 4;
    const ALL = 100;
    
    public static $level = self::NONE;

    public static function error($message) {
        if(self::$level >= self::ERROR ) {
            error_log("ERROR: ". $message);
        }
    }
    
    public static function warn($message) {
        if(self::$level > self::WARN ){
            error_log("WARN: " . $message);
        }
    }
    
    public static function info($message) {
        if( self::$level > self::INFO ){
            error_log("INFO: ". $message);
        }
    }
    
    public static function debug($message) {
        if( self::$level > self::DEBUG ){
            error_log("DEBUG: ". print_r($message, true));
        }
    }
}
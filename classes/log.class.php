<?php

namespace baobab;

class LogLevel {

    const NONE = 0;
    const ERROR = 1;
    const WARN = 2;
    const INFO = 3;
    const DEBUG = 4;
    const ALL = 100;

    public static $enums= array(
        self::NONE => "none",
        self::ERROR => "error",
        self::WARN => "warn",
        self::INFO => "info",
        self::DEBUG => "debug",
        self::ALL => "all",
    );

    public static function toString($enum) {
        return self::$enums[$enum];
    }
}

class Log {

    private static $_level = LogLevel::ALL;

    public static function error($message) {
        if(self::$_level >= LogLevel::ERROR ) {
            error_log("ERROR: ". $message);
        }
    }
    
    public static function warn($message) {
        if(self::$_level > LogLevel::WARN ){
            error_log("WARN: " . $message);
        }
    }
    
    public static function info($message) {
        if( self::$_level > LogLevel::INFO ){
            error_log("INFO: ". $message);
        }
    }
    
    public static function debug($message) {
        if( self::$_level > LogLevel::DEBUG ){
            error_log("DEBUG: ". print_r($message, true));
        }
    }

    public static function setLevel( $level) {
        self::$_level = $level;
    }

    public static function getLevel() {
        return LogLevel::toString(self::$_level);
    }

}

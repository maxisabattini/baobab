<?php

namespace baobab;

class LogLevel {

    const NONE  = 0;
    const ERROR = 1;
    const WARN  = 2;
    const INFO  = 3;
    const DEBUG = 4;
    const ALL   = 100;

    public static $enums= array(
        self::NONE  => "none",
        self::ERROR => "error",
        self::WARN  => "warn",
        self::INFO  => "info",
        self::DEBUG => "debug",
        self::ALL   => "all",
    );

    public static function toString($enum) {
        return self::$enums[$enum];
    }
}

interface LogOutput {
    public function lprint($value, $level);
}

class DefaultLogOutput implements LogOutput {
    public function lprint($value, $level) {
        if( $level == LogLevel::DEBUG ) {
            $value = print_r($value, true);
        }
        error_log( strtoupper( LogLevel::toString($level) ) . " : " . $value);
    }
}

class NullLogOutput implements LogOutput {
    public function lprint($value, $level) {}
}

class Log {

    private static $_level = LogLevel::ALL;
    
    public static $output = null;

    public static function error($message) {
        if(self::$_level >= LogLevel::ERROR ) {
            self::$output->lprint($message, LogLevel::ERROR);
        }
    }
    
    public static function warn($message) {
        if(self::$_level > LogLevel::WARN ){
            self::$output->lprint($message, LogLevel::WARN);            
        }
    }
    
    public static function info($message) {
        if( self::$_level > LogLevel::INFO ){
            self::$output->lprint($message, LogLevel::INFO);            
        }
    }
    
    public static function debug($message) {
        if( self::$_level > LogLevel::DEBUG ){
            self::$output->lprint($message, LogLevel::DEBUG);            
        }
    }

    public static function setLevel( $level) {
        self::$_level = $level;
    }

    public static function getLevel() {
        return LogLevel::toString(self::$_level);
    }
}
Log::$output = new DefaultLogOutput();

<?php

namespace baobab;

class Log {

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
}



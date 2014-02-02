<?php

namespace baobab;

require_once "queue.class.php";

class CssQueue extends Queue {

    private $_files = array();

    private static $_instance = null;

    public static function getInstance() {
        if( ! self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function addFile($file, $name="" ) {
        $name = $name ? $name : $file;
        $this->_files[$name]=$file;
        parent::add($name, array() );
    }

    public function flush() {
        foreach( $this->getAll() as $r ) {
            echo '<link href="'. $this->_files[$r] . '" media="all" rel="stylesheet" type="text/css">' . "\n";
        }
    }

    public function flushPacked() {

        $all = $this->getAll();
        $hash = md5( implode("",$all) );

        $app = App::getInstance();

        $appUrl = $app->getUrl();
        //$appName = urlencode($appUrl);
        $appName = md5($appUrl);

        $url = $app->config("packed_resources_url");
        if ( substr($url, 0, 7) != 'http://' && substr($url, 0, 8) != 'https://' && substr($url, 0, 2) != '//') {
            $url = $appUrl . "/" . $url;
        }
        $url = $url . "/$appName.$hash.css";

        $path = $app->config("packed_resources_path");
        $path = $path . "/$appName.$hash.css";


        if( ! file_exists($path) ) {

            $code = "";
            foreach( $all as $r ) {
                $resource = $this->_files[$r];
                if( substr($resource,0,2) == "//") {
                    $resource = "http:$resource";
                }
                $code .= file_get_contents( $resource );
            }

            //Minifier
            /* remove comments */
            $code = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $code);
            /* remove tabs, spaces, newlines, etc. */
            $code = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $code);

            //file_put_contents( $path, $code);
            if( !@file_put_contents( $path, $code) ) {
                Log::warn("Can not write packed css : $path");
                $this->flush();
                return;
            }
        }

        $md5 = md5_file( $path );
        echo '<link href="'. $url."?".$md5.'" media="all" rel="stylesheet" type="text/css">';
    }
}
<?php

namespace baobab;

require_once "log.class.php";

class Uri extends Parameters {

    public function __construct() {
        parent::__construct();

        $this->scheme='http';
        if ( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $this->scheme='https';
        }
        $this->host=$_SERVER['HTTP_HOST'];
        $this->port=$_SERVER["SERVER_PORT"];

        $self = $_SERVER['PHP_SELF'];
        $uri = $_SERVER['REQUEST_URI'];
        $pos = strrpos($self, "/");
        $this->user="";
        $this->pass="";
        $this->path=substr($self, 0, $pos+1);
        $this->query=(string) substr($uri, $pos+1);      //?adasd
        $this->fragment="";  //#adas    only with js
    }

    public function toString($overrides=array()) {        
        $this->merge($overrides);

        $pageURL = $this->scheme ."://".$this->host . $this->path . $this->query;
        if( $this->port && $this->port!=80  ) {
            $pageURL .= ":" . $this->port;
        }
        if( substr( $pageURL, -1) === "/" ) {
            $pageURL = substr( $pageURL, 0, strlen($pageURL) - 1 );
        }

        Log::info("Uri::toString()");
        Log::debug($pageURL);
        return $pageURL;
    }
}


<?php

namespace baobab;

require_once "log.class.php";

class Response {

    public function setHeader($name, $value) {
        $this->_headers[$name]=$value;
    }

    public function setExpires($timestamp, $zone="GMT") {
        $this->setHeader("Expires",gmdate("D, d M Y H:i:s $zone", $timestamp) );
    }

    public function setContent($type="application/octet-stream") {
        $this->setHeader("Content-Type", $type );
    }

    public function redirect($url) {
        $this->apply();
        header("location: $url");
    }

    public function apply($response=null) {
        foreach( $this->_headers as $key => $value ) {
            header("$key: $value");
        }
    }

    private $_headers=null;

    public function __construct() {
        $this->_headers=new Parameters();
    }
}

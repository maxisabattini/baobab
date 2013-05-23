<?php

namespace baobab;

class JSEnqueuer extends Enqueuer {

    private $_scripts = array();

    private static $instance = null;

    public static function getInstance() {
        if( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addFile($file, $name="", $depend = array() ) {
        $name = $name ? $name : $file;
        $this->_files[$name]=$file;

        $this->_scripts[$name]= array(
            "type"  =>  "file",
            "code"  =>  $file,
        );
        parent::add($name, $depend);
    }

    public function addScript($script, $name="", $depend = array() ) {
        if(!$name) {
            $name = md5(time());
        }
        $this->_scripts[$name]= array(
            "type"  =>  "script",
            "code"  =>  $script,
        );
        parent::add($name, $depend);
    }

    private $_beginCode = false;

    public function beginCode() {
        if($this->_beginCode) {
            ob_end_clean();
        }
        ob_start();
        $this->_beginCode=true;
    }

    public function endCode($name="", $depend = array()) {
        if($this->_beginCode) {
            $content = ob_get_clean();
            $content = preg_replace("/\<script[^>]*\>/", "", $content);
            $content = preg_replace("/\<\/script\>/", "", $content);
            $this->addScript( $content, $name, $depend );
            $this->_beginCode=false;
        }
    }

    public function flush() {
        echo "<pre>";
        var_dump($this->getAll());
        foreach( $this->getAll() as $r ) {
            if( $this->_scripts[$r]["type"] == "script" ) {
                echo "<script>" . $this->_scripts[$r]["code"] . "</script>\n";
            } else {
                echo "<script src='" . $this->_scripts[$r]["code"] . "'></script>\n";
            }
        }
    }

}
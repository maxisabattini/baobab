<?php

namespace baobab;

require_once "queue.class.php";

class JSQueue extends Queue {

    private $_scripts = array();
    private $_files = array();

    private static $_instance = null;

    public static function getInstance() {
        if( ! self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
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
            //$name = md5(time());
            $name = md5($script);
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
        foreach( $this->getAll() as $r ) {
            if( $this->_scripts[$r]["type"] == "script" ) {
                echo "<script>" . $this->_scripts[$r]["code"] . "</script>\n";
            } else {
                echo "<script src='" . $this->_scripts[$r]["code"] . "'></script>\n";
            }
        }
    }

    public function flushPacked() {

        $all = $this->getAll();

        $allReal = array();
        foreach( $all as $r ) {
            $allReal[]=$this->_scripts[$r]["code"];
        }

        $hash = md5( implode("",$allReal) );

        $cfg = Config::getInstance();
        $app = App::getInstance();

        $path = $cfg->get("statics_paths", $app->path . "/temp" );
        $path = $path . "/all.$hash.js";

        $appUrl = $app->getBaseUrl();
        $url = $cfg->get("statics_url", $appUrl . "/temp" );
        $url = $url . "/all.$hash.js";

        if( ! file_exists($path) ) {

            $code = "";
            foreach( $all as $r ) {
                if( $this->_scripts[$r]["type"] == "script" ) {
                    $code .= $this->_scripts[$r]["code"];
                } else {
                    $code .= file_get_contents($this->_scripts[$r]["code"]);
                }
            }

            //Minifier
            $jShrinkPath = $cfg->get("jshrin_path", $app->baoPath . "/libs/JShrink/src/JShrink/Minifier.php" );
            if(file_exists($jShrinkPath)){
                require_once $jShrinkPath;
                $code = JShrink\Minifier::minify($code, array('flaggedComments' => false));
            } else {
                Log::warn("Can not load minifier.php");
            }

            file_put_contents( $path, $code);
        }

        $md5 = md5_file( $path );
        echo "<script src='".$url."?".$md5."'></script>\n";
    }

}
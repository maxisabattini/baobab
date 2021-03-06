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

    public function addScript($script, $name=false, $depend = array() ) {
        if(!$name) {
            $name = md5($script);
        }
        $script = preg_replace("/\<script[^>]*\>/", "", $script);
        $script = preg_replace("/\<\/script\>/", "", $script);
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

    public function endCode($name=false, $depend = array()) {
        if($this->_beginCode) {
            $content = ob_get_clean();
            $content = preg_replace("/\<script[^>]*\>/", "", $content);
            $content = preg_replace("/\<\/script\>/", "", $content);
            $this->addScript( $content, $name, $depend );
            $this->_beginCode=false;
        }
    }

    public function get( $packed=false ) {

        $buffer="";

        $all = $this->getAll();
        if(!$all) {
            return "";
        }

        $app = App::getInstance();
        $packed = $app->config("packed_resources") || $packed;

        if(!$packed) {
            $buffer="";
            foreach( $all as $r ) {
                if( $this->_scripts[$r]["type"] == "script" ) {
                    $buffer.=$this->_scripts[$r]["code"];
                } else {
                    $buffer.= file_get_contents( $this->_scripts[$r]["code"] );
                }
            }
        } else {
            $results=$this->_resolvePacked($all);
            $buffer=file_get_contents( $results["path"] );
        }
        $this->clear();
        return $buffer;
    }

    public function flush( $packed=false ) {

        $app = App::getInstance();
        $packed = $app->config("packed_resources") || $packed;

        $all = $this->getAll();
        if(!$all) {
            return;
        }

        if(!$packed) {
            foreach( $all as $r ) {
                if( $this->_scripts[$r]["type"] == "script" ) {
                    echo "<script>" . $this->_scripts[$r]["code"] . "</script>\n";
                } else {
                    echo "<script src='" . $this->_scripts[$r]["code"] . "'></script>\n";
                }
            }
        } else {
            $results=$this->_resolvePacked($all);
            $url=$results["url"];
            echo "<script async src='".$url."'></script>\n";
        }
        $this->clear();
    }

    //Compat
    public function flushPacked() {
        return $this->flush( true );
    }

    private function _resolvePacked($all){

        $app = App::getInstance();

        $duration = $app->config("statics_resource_js_cache_time");
        if(!$duration) {
            $duration= 60 * 10 ;
        }

        $key = "JSQueue-" . $app->getUrl(false);
        Log::info("JSQueue for : $key" );

        $cache = Cache::getInstance();
        $packed = $cache->get($key, null , $duration );
        if(!$packed) {
            $packed = $this->_resolvePackedReal($all);
            if( ! is_null($packed) ) {
                $cache->set($key, $packed, $duration );
            }
        }

        Log::debug($packed);
        return $packed;
    }

    private function _resolvePackedReal($all) {

        $allReal = array();
        foreach( $all as $r ) {
            $allReal[]=$this->_scripts[$r]["code"];
        }

        $hash = md5( implode("",$allReal) );

        $app = App::getInstance();

        $appUrl = $app->config("url_base");
        //$appName = urlencode($appUrl);
        $appName = md5($appUrl);

        $url = $app->config("packed_resources_url");
        if ( substr($url, 0, 7) != 'http://' && substr($url, 0, 8) != 'https://' && substr($url, 0, 2) != '//') {
            $url = $appUrl . "/" . $url;
        }
        $url = $url . "/$appName.$hash.js";

        $path = $app->config("packed_resources_path");
        $path = $path . "/$appName.$hash.js";

        if( ! file_exists($path) ) {

            $code = "";
            foreach( $all as $r ) {
                if( $this->_scripts[$r]["type"] == "script" ) {
                    $content = $this->_scripts[$r]["code"];
                    $content = preg_replace("/\<script[^>]*\>/", "", $content);
                    $content = preg_replace("/\<\/script\>/", "", $content);
                    $code .= "\n/* JS_SCRIPT : $r */\n";
                    $code .= $content;
                    //$code .= $this->_scripts[$r]["code"];
                } else {
                    if( file_exists($this->_scripts[$r]["code"]) ) {
                        Log::info( "loading JS path: " . $this->_scripts[$r]["code"]  );
                        $code .= "\n/* JS_FILE : $r */\n";
                        $code .= file_get_contents( $this->_scripts[$r]["code"] );
                    } else {
                        Log::warn( "Can not load JS path: " . $this->_scripts[$r]["code"]  );
                    }
                }
            }

            //Minifier
            $jShrinkPath = BAOBAB_PATH . "/libs/JShrink/src/JShrink/Minifier.php";
            if( ! $app->config("packed_no_minify") && file_exists($jShrinkPath) ){
                require_once $jShrinkPath;
                $code = \JShrink\Minifier::minify($code, array('flaggedComments' => false));
            } else {
                Log::warn("Can not load minifier.php");
            }

            if( !@file_put_contents( $path, $code) ) {
                Log::warn("Can not write packed js : $path");
                return false;
            };
        }

        return array(
            "url"	=>	$url,
            "path"	=>	$path,
        );
    }

}

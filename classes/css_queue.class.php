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
	
	public function flush( $packed=false ) {

		$app = App::getInstance();
		$packed = $app->config("packed_resources") || $packed;
	
		$all = $this->getAll();
		if(!$all) {
            return;
        }
	
		if(!$packed) {			
		    foreach( $all as $r ) {
				echo '<link href="'. $this->_files[$r] . '" media="all" rel="stylesheet" type="text/css">' . "\n";
			}
		} else {
			$results=$this->_resolvePacked($all);
			$url=$results["url"];
			echo '<link href="'. $url.'" media="all" rel="stylesheet" type="text/css">';
		}	
		$this->clear();
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
			$buffer=$this->_getFilesContent($all);
		} else {
			$results=$this->_resolvePacked($all);
			$buffer=file_get_contents( $results["path"] );
		}	
		$this->clear();
		return $buffer;
	}

	//Compat
    public function flushPacked() {
		return $this->flush( true );
    }

	private function _resolvePacked($all) {
	
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

		$this->_pack($path, $all);
		
		return array(
			"url"	=>	$url,
			"path"	=>	$path,
		);
	}
	
	private function _pack($path, $all){

        if( ! file_exists($path) ) {

            $code = $this->_getFilesContent($all);
            
            //Minifier
            /* remove comments */
            $code = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $code);
            // Remove space after colons
            $code = str_replace(': ', ':', $code);
            /* remove tabs, spaces, newlines, etc. */
            $code = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $code);

            //file_put_contents( $path, $code);
            if( !@file_put_contents( $path, $code) ) {
                Log::warn("Can not write packed css : $path");
                $this->flush();
                return;
            }
        }
	}
	
	private function _getFilesContent($all) {	
		$code = "";
		foreach( $all as $r ) {
			$resource = $this->_files[$r];
			if( substr($resource,0,2) == "//") {
				$resource = "http:$resource";
			}
			$code .= file_get_contents( $resource );
		}
		return $code;
	}
}

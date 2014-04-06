<?php

namespace baobab;

class Config {

    public function __construct($name = 'default') {
        if($name=="global") {
            $this->_initGlobal();
        }
        $this->_name=$name;
    }

    public static function getInstance($name = 'global') {
        if( ! isset(self::$_instances[$name]) ) {
            self::$_instances[$name] = new self($name);
        }
        return self::$_instances[$name];
    }

    public function has($key) {
        return isset($this->_settings[$key]);
    }        
    
    public function get($key, $default=null) {
        if (!isset($this->_settings[$key])) {
            return $default;
        } 
        return $this->_settings[$key];
    }
    
    public function set($key, $value) {
        $this->_settings[$key] = $value;
    }
    
    public function loadFile( $file ) {

        if ( ! file_exists( $file )) {
			Log::warn("Can not load file: $file");
			return false ;
		}

        $ext = substr($file, -4, 4);

        switch($ext) {
            case ".php":
                return $this->_loadPhpFile($file);
                break;
            case ".ini":
                return $this->_loadIniFile($file);
                break;
            case ".json":
            case ".js":
                return $this->_loadJsFile($file);
                break;
            default:
                break;
        }

        return false;
    }	


    /*
    * Private members
    **/
    
    private static $_instances;
    private $_name;
    private $_settings;
    
    private function _initGlobal() {
        $cfg = array(); 

        $files = $this->_getConfigFilesList();
        foreach ($files as $file) {
            $this->loadFile( $file );
        }
    }

    private function _getConfigFilesList() {
        $files = array ();

        if(! defined("BAO_PATH") ) {
            define("BAO_PATH", "");
        }

        $dir = BAO_PATH . '/cfg/';

        if ( is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ( ! is_dir($dir . $file) && substr($file, -4, 4) == '.php') 
                        $files[] = $dir . $file; 
                }
                closedir($dh);
                sort($files); 
            }
        }
        return $files;
    }

    private function _loadPhpFile($file) {
        $cfg = array();

        $result = include ( $file );
        
        if(!$cfg && is_array($result) ) {
            $cfg = &$result;
        }

        foreach ($cfg as $key => $val ) {
            $this->set($key, $val);
        }
        return true;
    }
	
    private function _loadIniFile($file) {

        $configs = parse_ini_file($file);

        foreach ($configs as $key => $val ) {
            $this->set($key, $val);
        }
        return true;
    }

    private function _loadJsFile($file) {

        $configs = json_decode($file, true);

        foreach ($configs as $key => $val ) {
            $this->set($key, $val);
        }
        return true;
    }
}

<?php

namespace baobab;

class Config {

	private static $_instance;	
	private $settings;

	public static function getInstance() {
		if ( ! isset(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	private function __construct() {
		$this->init();
	}

	private function init() {
		$cfg = array () ; 

		$files = $this->getConfigFilesList();
		foreach ($files as $file) 
		{
			$this->loadCfgFile( $file );
		}
	}

	private function getConfigFilesList() {
		$files = array (); 
		$dir = BAO_PATH . '/cfg/';

		if ( is_dir($dir)) 
		{
		    if ($dh = opendir($dir)) 
		    {
				while (($file = readdir($dh)) !== false) 
				{
					if ( ! is_dir($dir . $file) && substr($file, -4, 4) == '.php') 
						$files[] = $dir . $file; 
				}
				closedir($dh);

				sort($files); 
		    }
		}
		return $files;
	}
	
	public function loadCfgFile( $file ) {

        if ( ! file_exists( $file )) return false ;

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

    private function _loadPhpFile($file) {
		$cfg = array();

        $result = include ( $file );
		
		if(!$cfg && is_array($result) ) {
			$cfg = &$result;
		}

        foreach ($cfg as $key => $val ) {
            $this->_set($key, $val);
        }
        return true;
    }
	
    private function _loadIniFile($file) {

        $configs = parse_ini_file($file);

        foreach ($configs as $key => $val ) {
            $this->_set($key, $val);
        }
        return true;
    }

    private function _loadJsFile($file) {

        $configs = json_decode($file, true);

        foreach ($configs as $key => $val ) {
            $this->_set($key, $val);
        }
        return true;
    }

	public static function get($name, $default=null) {
		return self::getInstance()->_get($name, $default);
	}

	private function _set($name, $value) {
		$this->settings[$name] = $value;
	}

	private function _get($name, $default) {
		if (!isset($this->settings[$name])) return $default;
		return $this->settings[$name]; 
	}
}

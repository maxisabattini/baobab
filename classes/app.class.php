<?php

namespace baobab;
require_once "config.class.php";
require_once "log.class.php";
require_once "controller.class.php";
require_once "js_queue.class.php";
require_once "css_queue.class.php";

if( ! defined( "BAOBAB_PATH" ) ) {
    define( "BAOBAB_PATH", dirname ( dirname(__FILE__) ) );
}

class App {

    public static function getInstance($name = 'default') {
        if( ! isset(self::$_instances[$name]) ) {
            self::$_instances[$name] = new self();
        }
        return self::$_instances[$name];
    }

    // Paths

    public function getPath() {
        return $this->_path;
    }

    public function setPath($path) {
        $this->_path = $path;
    }

    // URL Management

    public function getUrl() {
        return $this->_url;
    }

    public function getUrlBase() {
        $url = $this->_url;

        $pf = "/index.php";
        if(substr($url , -strlen($pf) ) === $pf) {
            $url = substr($url, 0, -strlen($pf));
        }
        return $url;
    }

    public function getUrlParts() {
        return $this->_siteUrlParts;
    }

    // Pages

    public function route( $routes ) {

        $pages = &$routes;

        if( isset( $pages["*"] ) ) {
            $this->appInfo["*"]=$pages["*"];
        }

        $uri = $this->getUrlParts();
        $request = $uri["query"];

        if( $pos = strpos( $request, "?") ) {
            $request = substr($request, 0, $pos);
        }

        if( substr( $request, -1) === "/" ) {
            $request = substr( $request, 0, strlen($request) - 1 );
        }	

        //Set the pages "page.real" => "something"
        foreach($pages as $k => $v) {
            if( isset($v["page"]) ) {
                $this->pages[ $v["page"] ] = $k;
            }			
        }	

        if(!$request) {
            $request=".";
        }

        if( isset( $pages[$request] ) ) {
            $this->pageInfo = $pages[$request];			
        }		

        if( ! isset( $this->pageInfo["page"] ) ) {	//no rewrite for this page
            $parts = explode("/", $request );		
            $view = implode(".", $parts);
            
            //Reasign pageInfo
            if( isset($this->pages[$view]) ) {
                $this->pageInfo = $pages[ $this->pages[$view] ];
            }
            
            //By default pageInfo
            if( ! isset( $this->pageInfo["page"] ) ) {
                $this->pageInfo["page"] = $view;
            }
        }

        $this->appInfo["page"]= &$this->pageInfo;

        $filePath = $this->_path . "/pages/" . $this->pageInfo["page"] . ".php";

        //404 page
        if( ! file_exists($filePath) ) {
            Log::warn("_APP_: Page not found : $filePath");
            $filePath = $this->_path . "/pages/404.php";
            $this->pageInfo["page"]="404";
        }

        if( file_exists( $filePath ) ) {			
            $this->loadController( $this->pageInfo["page"], $this->pageInfo );
            include $filePath;	
        } else {
            header('HTTP/1.0 404 Not Found');
            die;
        }
	}

    public function info($section, $key, $default=false){
        return isset( $this->appInfo[$section][$key] ) ? $this->appInfo[$section][$key] : $default;
    }

    public function pageInfo() {
        return $this->pageInfo;
    }

    public function pageUrl($page) {
        if(!$page) {
            $page=".";
        }

        if( isset($this->pages[$page]) ) {
            $url = $this->_url;

            $pf = "/index.php";
            if(substr($url , -strlen($pf) ) === $pf) {
                $url = substr($url, 0, -strlen($pf));
            }

            return $url . "$pf/" . $this->pages[$page];
        } else {
            $parts = explode(".", $page );

            return $this->_url . "/" . implode("/", $parts) ;
        }
    }


    // Controllers & Views

    public function render($view, $params = array()) {

        $prefix="";
        $name=$view;
        $class = ucfirst($name);
        if( strpos($view, ".") !== false ) {
            list($prefix, $name) = explode(".", $view);
            $class = ucfirst($prefix) . ucfirst($name);
        }

        if ( $prefix == "app" ) {
            //CORE
            $viewFile = BAOBAB_PATH  . "/$view.php";
            $controllerFile = BAOBAB_PATH . "/controllers/$view.c.php";
        } else {
            //PAGES
            $viewFile = $this->_path . "/pages/$view.php";
            $controllerFile = $this->_path . "/controllers/$view.c.php";
            if( ! file_exists( $viewFile ) ) {
                //MODULES
                $viewFile = $this->_path . "/modules/$view.m.php";
                $controllerFile = $this->_path . "/controllers/$view.mc.php";
            }
        }

        if( ! file_exists( $viewFile ) ) {
            Log::error("_APP_: can not load view : $view");
            return;
        }

        if ( file_exists( $controllerFile ) ) {
            require_once $controllerFile;
            $className = "\\baobab\\$class"."Controller";
            $controller = new $className( $viewFile, $params );
        } else {
            Log::warn("_APP_: can not load controller : $controllerFile");
            $controller = new Controller( $viewFile, $params );
        }
        $controller->render();
    }

	// Template management

	private $_sections = array();
	
    public function startSection($name) {
        ob_start();
        $this->_sections[$name]="";
    }

    public function endSection($name) {
        $this->_sections[$name]= ob_get_clean();
    }
	
	public function addSection($name, $content) {
		$this->_sections[$name]=$content;
	}
	
	public function removeSection($name) {
		unset($this->_sections[$name]);
	}
	
	public function getSection($name) {
        return $this->_sections[$name];
    }
	
	public function renderAsTemplate($view, $params = array() ) {		
		$this->render( $view, array_merge($this->_sections, $params) );
	}

    // Private members

    protected static $_instances = array();
    private $_path;
    private $_url;
    private $_siteUrlParts= array();
    private $pageInfo = array();
    private $appInfo = array();

    private function __construct() {
        //$this->baoPath = dirname ( dirname(__FILE__) );
        //$this->path = dirname(dirname(dirname( $this->baoPath ) ) );

        $baobabPath = dirname ( dirname(__FILE__) );
        $this->_path = dirname(dirname(dirname( $baobabPath ) ) );


        $this->_siteUrlParts = new UrlParts();
        $this->_url = $this->_siteUrlParts->toString();

        //$this->makeUrl( array_merge($parts, array( "query" => "" ) ));
        //$this->parseCurrentUrl();
    }

    protected function loadController( $view , $params = array() ) {
        $viewFile = $this->_path . "/$view.php";
        $controllerFile = $this->_path . "/controllers/$view.c.php";
        if ( file_exists( $controllerFile ) ) {
            require_once $controllerFile;
            $class = ucfirst($view);
            $className = "\\baobab\\$class"."Controller";
            $controller = new $className( $viewFile, $params );
        } else {
            $controller = new Controller( $viewFile, $params );
        }
        $controller->exposeVarsAsGlobals();
    }


}

class UrlParts implements \ArrayAccess {

    private $_container = array();

    public function __construct() {
        $this->_container["scheme"]='http';
        if ( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $this->_container["scheme"]='https';
        }
        $this->_container["host"]=$_SERVER['HTTP_HOST'];
        $this->_container["port"]=$_SERVER["SERVER_PORT"];

        $self = $_SERVER['PHP_SELF'];
        $uri = $_SERVER['REQUEST_URI'];
        $pos = strrpos($self, "/");
        $this->_container["user"]="";
        $this->_container["pass"]="";
        $this->_container["path"]=substr($self, 0, $pos+1);
        $this->_container["query"]=(string) substr($uri, $pos+1);      //?adasd
        $this->_container["fragment"]="";  //#adas    only with js
    }

    public function toString() {
        $pageURL = $this->_container["scheme"]."://".$this->_container["host"].$this->_container["path"].$this->_container["query"];
        if($this->_container["port"]!=80) {
            $pageURL .= ":" . $this->_container["port"];
        }
        if( substr( $pageURL, -1) === "/" ) {
            $pageURL = substr( $pageURL, 0, strlen($pageURL) - 1 );
        }
        return $pageURL;
    }

    public function offsetSet($offset, $value) {
        $this->_container[$offset] = $value;
    }

    public function offsetExists($offset) {
        return isset($this->_container[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->_container[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->_container[$offset]) ? $this->_container[$offset] : null;
    }
}

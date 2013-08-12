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
        return $this->_urlBase;
    }

    public function getUrlParts() {
        return $this->_siteUrlParts;
    }

    // Pages

    public function route( $routes ) {

        Log::debug("Starting route");

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

        Log::debug("Request: $request");

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

        //Loading View
        $viewPath = $this->_path . "/pages/" . $this->pageInfo["page"] . ".php";
        $hasView = file_exists($viewPath);

        if( ! $hasView ) {
            Log::warn("_APP_: Page View not found : $viewPath");
        }

        //Loading Controller
        $controller = $this->loadController( $this->pageInfo["page"], $this->pageInfo );

        Log::debug("Loading PAGE ( " . $this->pageInfo["page"] ." ) controller => " . get_class($controller));

        $hasController = get_class($controller) != "Controller";

        //No View and controller - Custom 404 page
        if( ! $hasView && ! $hasController ) {
            
            Log::warn("_APP_: 404 launched");

            //loading Custom 404
            $this->pageInfo["page"]="404";
            $controller = $this->loadController( $this->pageInfo["page"], $this->pageInfo );

            //No custom 404
            if( get_class($controller) == "Controller" ) {
                header('HTTP/1.0 404 Not Found');
                die;
            }
        }

        $controller->render();
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
            $url = $this->_urlBase;
            $pf="";
            if( ! $this->info("*", "rewrite", false) ) {
                $pf="/index.php";
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

        $namespace="";
        if ( $prefix == "app" ) {
            //CORE
            $viewFile = BAOBAB_PATH  . "/$view.php";
            $controllerFile = BAOBAB_PATH . "/controllers/$view.c.php";
            $namespace="\\baobab";
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

            $className = "$namespace\\$class"."Controller";
            $controller = new $className( $viewFile, $params );
        } else {
            Log::warn("_APP_: can not load controller : $controllerFile");
            $controller = new Controller( $viewFile, $params );
        }

        Log::info("VIEW: " . $viewFile);
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
    private $_urlBase;
    private $_siteUrlParts= array();
    private $pageInfo = array();
    private $appInfo = array();

    private function __construct() {
        $baobabPath = dirname ( dirname(__FILE__) );
        $this->_path = dirname(dirname(dirname( $baobabPath ) ) );


        $this->_siteUrlParts = new UrlParts();
        $this->_url = $this->_siteUrlParts->toString();

        $path = $this->_siteUrlParts["path"];
        if( $pos = strpos($path, "index.php") ){
            $path = substr($path, 0, $pos);
        }
        $this->_urlBase = $this->_siteUrlParts->toString(array("path"=>$path, "query"=>"", "fragment"=>""));
    }

    protected function loadController( $view , $params = array() ) {
        $viewFile = $this->_path . "/pages/$view.php";
        $controllerFile = $this->_path . "/controllers/$view.c.php";
        Log::debug("Controller File => $controllerFile ");
        if ( file_exists( $controllerFile ) ) {
            require_once $controllerFile;
            $class = ucfirst($view);
            $class = str_replace('-','_', $class);
            $className = "\\$class"."Controller";

            Log::debug("Controller Class => $className ");

            $controller = new $className( $viewFile, $params );
        } else {
            $controller = new Controller( $viewFile, $params );
        }

        return $controller;
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

    public function toString($parts=array()) {
        $container = array_merge($this->_container, $parts);

        $pageURL = $container["scheme"]."://".$container["host"].$container["path"].$container["query"];
        if($container["port"]!=80) {
            $pageURL .= ":" . $container["port"];
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

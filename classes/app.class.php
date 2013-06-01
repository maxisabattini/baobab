<?php

namespace baobab;

require_once "log.class.php";
require_once "controller.class.php";
require_once "js_enqueuer.class.php";
require_once "css_enqueuer.class.php";

class App {

	public $sitePath;
	public $siteUrl;
	
	private $_path;
	
	private $_siteUrlParts= array();
	
	private function __construct() {
	    $this->_path = dirname ( dirname(__FILE__) );
	    $this->sitePath = dirname(dirname(dirname( $this->_path ) ) );
	}
	
	public function setSitePath($path) {    
	    $this->sitePath = $path;
	}	
	
	public function getSiteUrl() {
		return $this->_siteUrlParts;
	}
	
	public function makeUrl($parts) {
		$pageURL = $parts["scheme"] . "://" . $parts["host"] . $parts["path"] . $parts["query"];		
		if($parts["port"]!=80) {
			$pageURL .= ":" . $parts["port"];
		}		
		if( substr( $pageURL, -1) === "/" ) {
			$pageURL = substr( $pageURL, 0, strlen($pageURL) - 1 );
		}		
		return $pageURL;
	}
	
	private $pageInfo = array();
	
	public function getPageInfo() {
		return $this->pageInfo;
	}
	
	private $appInfo = array();	
	public function getInfo($section, $key, $default=false){
	    return isset( $this->appInfo[$section][$key] ) ? $this->appInfo[$section][$key] : $default;
	}
	
	public function route( $routes ) {
	    
		$parts=array();		
		$parts["scheme"]='http';
		if ( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {			
			$parts["scheme"]='https';	
		}		
		$parts["host"]=$_SERVER['HTTP_HOST'];		
		$parts["port"]=$_SERVER["SERVER_PORT"];		
		
		$self = $_SERVER['PHP_SELF'];
		$uri = $_SERVER['REQUEST_URI'];
		$pos = strrpos($self, "/");
		$parts["user"]="";
		$parts["pass"]="";
		$parts["path"]=substr($self, 0, $pos+1);
		$parts["query"]=(string) substr($uri, $pos+1);		//?adasd
		$parts["fragment"]="";	//#adas    only with js
		$this->_siteUrlParts = $parts;
		$this->siteUrl = $this->makeUrl( array_merge($parts, array( "query" => "" ) ));

	    $pages = &$routes;
	    
	    if( isset( $pages["*"] ) ) {		    
		    $this->appInfo["*"]=$pages["*"];
		}
	    
		$uri = $this->getSiteUrl();
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
		
		$filePath = $this->sitePath . "/pages/" . $this->pageInfo["page"] . ".php";
		
		//404 page
		if( ! file_exists($filePath) ) {
			Log::warn("_APP_: Page not found : $filePath");
			$filePath = $this->sitePath . "/pages/404.php";
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
	
	public function getPageUrl($page) {
		if(!$page) {
			$page=".";
		}
		
		if( isset($this->pages[$page]) ) {	    
		    $url = $this->siteUrl;
		    
            $pf = "/index.php";		    		    
            if(substr($url , -strlen($pf) ) === $pf) {
                $url = substr($url, 0, -strlen($pf));
            }

			return $url . "$pf/" . $this->pages[$page];
		} else {
			$parts = explode(".", $page );
			
			return $this->siteUrl . "/" . implode("/", $parts) ;
		}
	}
	
	private static $_instance = null;

	public static function getInstance() {		
		if( ! self::$_instance ) {
			self::$_instance = new self();
		}	
		return self::$_instance;
	}
		
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
			$viewFile = $this->_path . "/$view.php";
			$controllerFile = $this->_path . "/controllers/$view.c.php";
		} else {
			//PAGES
			$viewFile = $this->sitePath . "/pages/$view.php";
			$controllerFile = $this->sitePath . "/controllers/$view.c.php";
			if( ! file_exists( $viewFile ) ) {
				//MODULES
				$viewFile = $this->sitePath . "/modules/$view.m.php";
				$controllerFile = $this->sitePath . "/controllers/$view.mc.php";
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

	public function loadController( $view , $params = array() ) {
        $viewFile = $this->sitePath . "/$view.php";
        $controllerFile = $this->sitePath . "/controllers/$view.c.php";
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
	
	//Template management	
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
}

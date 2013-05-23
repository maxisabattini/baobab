<?php

namespace baobab;

class App {

	public $sitePath;
	public $siteUrl;
	
	private $path;
	
	private $siteUrlParts= array();
	
	private function __construct() {
		$filePath = dirname(__FILE__);		
		$this->path = dirname ( $filePath );
		$this->sitePath = dirname(dirname(dirname( $this->path ) ) );
		
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
		$parts["query"]=substr($uri, $pos+1);		//?adasd
		$parts["fragment"]=substr($uri, $pos+1);	//#adas
		$this->siteUrlParts = $parts;
		
		$this->siteUrl = $this->makeUrl( array_merge($parts, array( "path" => "", "query" => "") ));	
	}
	
	public function getSiteUrl() {
		return $this->siteUrlParts;
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
	
	private $c = array();
	
	private $pageInfo = array();
	
	public function getPageInfo() {
		return $this->pageInfo;
	}
	
	public function route() {
		
		$uri = $this->getSiteUrl();
		$request = $uri["query"];
		
		if( substr( $request, -1) === "/" ) {
			$request = substr( $request, 0, strlen($request) - 1 );
		}	
		
		//Set the pages "page.real" => "something"
		global $pages;
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
		
		$filePath = $this->sitePath . "/pages/" . $this->pageInfo["page"] . ".php";
		
		//404 page
		if( ! file_exists($filePath) ) {
			Log::warn("Page not found : $filePath");
			$filePath = $this->sitePath . "/pages/404.php";
			$this->pageInfo["page"]="404";			
		}
		
		if( file_exists( $filePath ) ) {			
			$this->loadController( $this->pageInfo["page"], $this->pageInfo );
			include $filePath;	
		} else {
			header("Status: 404 Not Found");			
		}
	}
	
    public function getPageUrl($page) {
		if(!$page) {
			$page=".";
		}		
		if( isset($this->pages[$page]) ) {
			return $this->siteUrl . "/" . $this->pages[$page];
		} else {
			$parts = explode(".", $page );
			return $this->siteUrl . "/" . implode("/", $parts) ;
		}		
    }	
	
	private static $instance = null;

	public static function getInstance() {		
		if( ! self::$instance ) {
			self::$instance = new self();
		}	
		return self::$instance;
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
			$viewFile = $this->path . "/$view.php";
			$controllerFile = $this->path . "/controllers/$view.c.php";
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
			Log::error("can not load view : $view");
			return;
		}

		if ( file_exists( $controllerFile ) ) {
			require_once $controllerFile;
			$className = "\\baobab\\$class"."Controller";
			$controller = new $className( $viewFile, $params );
		} else {
            Log::warn("can not load controller : $controllerFile");
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
	
	private $sections = array();
	
    public function startSection($name) {
        ob_start();
		$this->sections[$name]="";
    }

    public function endSection($name) {
		//$this->sections[$name]=utf8_decode( ob_get_clean() );       
		$this->sections[$name]= ob_get_clean() ;       
    }
	
	public function addSection($name, $content) {
		$this->sections[$name]=$content;
	}
	
	public function removeSection($name) {
		unset($this->sections[$name]);		
	}
	
	public function getSection($name) {
        return $this->sections[$name];
    }
	
	public function renderAsTemplate($view, $params = array() ) {		
		$this->render( $view, array_merge($this->sections, $params) );
	}

	//Environment
	public function printEnv() {
		
		if( Config::get('env') !=  "PROD" ) {
			?>
			<span style="background: #000; color: #fff; padding: 5px; font-size: 20px; position: absolute; top:0; left:50%;" >
				<?php echo Config::get('env') ?>
			</span>
			<?php
		}		
	}
}

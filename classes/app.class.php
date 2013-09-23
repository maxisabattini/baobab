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

    /**
     * Get Instance
     *
     * @param string $name
     * @return App
     */
    public static function getInstance($name = 'default') {
        if( ! isset(self::$_instances[$name]) ) {
            self::$_instances[$name] = new self($name);
        }
        return self::$_instances[$name];
    }
    
    protected $_name = "";
    protected $_config = array(
        "path"              => "",
        "pages_path"         => "pages",
        "modules_path"      => "modules",
        "controller_path"   => "controllers",
    );
    
    public function config($key, $value=false) {
        if(!$value) {               
            if(is_object($key)){
                $key=(array)$key;
            }            
            if(is_array($key)){
                $this->_config = array_merge($this->_config, $key);                
            } else {    //Getter            
                return $this->_config[$key];
            }
        } else {
            $this->_config[$key]=$value;
        }
    }

    protected $_routes = array();
    protected $_routeUsed = false;

    protected $_pages = array();
    protected $_r_pages = array();
    protected $_currentPage = array();

    public function map($pattern, $callable, $params=array(), $methods=array()){
        $url = $this->_url;
        $uri = "/" . $url["query"];
        $this->_routes[$pattern] = new Route($pattern, $uri, $callable, $params, $methods);

        if(! is_callable($callable) ) {
            $this->_pages[$pattern]=$callable;
            $this->_r_pages[$callable]=$pattern;
        }
    }

    public function pageUrl($page) {
        return $this->_r_pages[$page];
    }

    public function pageParams($page=false) {
        if(!$page) {
            $page=$this->_currentPage;
        }

        $pattern = $this->_r_pages[$page];
        $route = $this->_routes[$pattern];
        return $route->params;
    }

    public function get($pattern, $callable ){
        $this->map($pattern, $callable, array("get"));
    }
    
    public function post($pattern, $callable ){
        $this->map($pattern, $callable, array("post"));
    }

    public function put($pattern, $callable ){
        $this->map($pattern, $callable, array("put"));
    }

    protected function executeRoute($route){

        $this->_routeUsed = true;
        $params = $route->params;
        $callable = $route->callable;
        if(!$callable) {
            Log::warn("Not callable or controller for this map: ". $route->pattern );
        }

        if(is_callable($callable)) {
            $callable($params);
        } else {    //Load Controller
            $this->_currentPage = $route->pattern;
            $this->_routeUsed = $this->render($route->callable, $route->params, false);
        }
    }

    public function render($name, $params=array(), $isModule=true) {

        $viewPath = $isModule ? $this->config("modules_path") : $this->config("pages_path") ;
        $viewFile = $viewPath . "/" . $name . ".php";
        Log::debug( "Using view file : ( " . $viewFile ." ) " );
        $hasView = file_exists($viewFile);

        $controllerPath = $this->config("controller_path");
        $controllerFile = $controllerPath . "/" . $name . ".". ($isModule ? 'm' : '') ."c.php";
        Log::debug( "Using view file : ( " . $controllerFile ." ) " );
        $hasController = file_exists($controllerFile);

        Log::debug(
            "Loading " . ($isModule ? 'MODULE' : 'PAGE') . " ( " . $name ." ) " .
                " controller => " . ( $hasController ? "YES" : "NO") .
                " view => " . ( $hasView ? "YES" : "NO" )
        );

        $controller = new Controller( $viewFile, $params );

        if( $hasController ) {
            require_once $controllerFile;

            $class = ucfirst($name);
            $class = str_replace('-','_', $class);
            $className = "\\$class"."Controller";

            Log::debug("Controller Class => $className ");

            if( class_exists($className)) {
                $controller = new $className( $viewFile, $params );
            } else {
                Log::error("Controller Class not exist.");
            }
        }

        if( ! $hasView && ! $hasController ) {
             Log::warn( ($isModule ? 'MODULE' : 'PAGE') ." => $name not executed");
             return false;
        } else {
            $controller->render();
        }
        return true;
    }

    // Paths
    /*
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
        */

    // Page

    public function route( $routes = array() ) {

        //TODO: import routes array
        foreach($routes as $k=>$v) {
            if(isset($v["page"])) {
                $callable = $v["page"];
                unset($v["page"]);
                $this->map($k, $callable, $v);
            }
        }

        Log::debug("Starting route");

        foreach($this->_routes as $route) {
            if ($route->isMatched) {
                $this->executeRoute($route);
                break;
            }
        }

        //TODO: launch 404
        if( ! $this->_routeUsed ) {
            header('HTTP/1.0 404 Not Found');
        }
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

    protected $_url= array();

    private function __construct($name="") {
        
        $this->_name = $name;

        $baobabPath = dirname ( dirname(__FILE__) );
        $_path = dirname(dirname(dirname( $baobabPath ) ) );
        $this->config("path", $_path);

        $this->_url = new Url();

        $_url = $this->_url->toString();

        $this->config("url", $_url);

        $path = $this->_url["path"];
        if( $pos = strpos($path, "index.php") ){
            $path = substr($path, 0, $pos);
        }
        $_urlBase = $this->_url->toString(array("port"=> "", "path"=>$path, "query"=>"", "fragment"=>""));
        $this->config("url_base", $_urlBase);

	    Log::debug($_urlBase);

    }
}

class Url implements \ArrayAccess {

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
        if( $container["port"] && $container["port"]!=80  ) {
            $pageURL .= ":" . $container["port"];
        }
        if( substr( $pageURL, -1) === "/" ) {
            $pageURL = substr( $pageURL, 0, strlen($pageURL) - 1 );
        }

    	Log::info("UrlParts::toString");
	    Log::debug($pageURL);
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

class Route {

    public $pattern;
    public $callable;
    public $params;

    public $conditions = array();

    public $methods = array();

    public $isMatched = false;

    function __construct($pattern, $uri, $callable=null, $params=array(), $methods=array()){

        $this->pattern = $pattern;
        $this->callable=$callable;
        $this->methods=$methods;


        //$this->params = array();
        $this->params = $params;

        $p_names = array(); $p_values = array();

        preg_match_all('@:([\w]+)@', $pattern, $p_names, PREG_PATTERN_ORDER);
        $p_names = $p_names[0];

        $url_regex = preg_replace_callback('@:[\w]+@', array($this, 'regex_url'), $pattern);
        $url_regex .= '/?';

        if (preg_match('@^' . $url_regex . '$@', $uri, $p_values)) {
            array_shift($p_values);

            foreach($p_names as $index => $value) $this->params[substr($value,1)] = urldecode($p_values[$index]);

            //foreach($target as $key => $value) $this->params[$key] = $value;

            $this->isMatched = true;
        }

        unset($p_names); unset($p_values);
    }


    public function matches($uri){}
    
    function regex_url($matches) {
    	$key = str_replace(':', '', $matches[0]);
    	if (array_key_exists($key, $this->conditions)) {
    		return '('.$this->conditions[$key].')';
    	}
    	else {
    		return '([a-zA-Z0-9_\+\-%]+)';
    	}
    }
}


/*
 class Router {
    public $request_uri;
    public $routes;
    public $controller, $controller_name;
    public $action, $id;
    public $params;
    public $route_found = false;

    public function __construct() {
        $request = $_SERVER['REQUEST_URI'];
        $pos = strpos($request, '?');
        if ($pos) $request = substr($request, 0, $pos);

        $this->request_uri = $request;
        $this->routes = array();
    }

    public function map($rule, $target=array(), $conditions=array()) {
        $this->routes[$rule] = new Route($rule, $this->request_uri, $target, $conditions);
    }

    public function default_routes() {
        $this->map('/:controller');
        $this->map('/:controller/:action');
        $this->map('/:controller/:action/:id');
    }

    private function set_route($route) {
        $this->route_found = true;
        $params = $route->params;
        $this->controller = $params['controller']; unset($params['controller']);
        $this->action = $params['action']; unset($params['action']);
        $this->id = $params['id'];
        $this->params = array_merge($params, $_GET);

        if (empty($this->controller)) $this->controller = ROUTER_DEFAULT_CONTROLLER;
        if (empty($this->action)) $this->action = ROUTER_DEFAULT_ACTION;
        if (empty($this->id)) $this->id = null;

        $w = explode('_', $this->controller);
        foreach($w as $k => $v) $w[$k] = ucfirst($v);
        $this->controller_name = implode('', $w);
    }

    public function execute() {
        foreach($this->routes as $route) {
            if ($route->is_matched) {
                $this->set_route($route);
                break;
            }
        }
    }
}

 */
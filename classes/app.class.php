<?php

namespace baobab;

if( ! defined( "BAOBAB_PATH" ) ) {
    define( "BAOBAB_PATH", dirname ( dirname(__FILE__) ) );
}

require_once "config.class.php";
require_once "log.class.php";
require_once "controller.class.php";
require_once "js_queue.class.php";
require_once "css_queue.class.php";
require_once "parameters.class.php";
require_once "uri.class.php";
require_once "request.class.php";
require_once "response.class.php";
require_once "route.class.php";

class App {

    public static function getInstance($name = 'default') {
        if( ! isset(self::$_instances[$name]) ) {
            self::$_instances[$name] = new self($name);
        }
        return self::$_instances[$name];
    }
    

    /*
    *	Route managment
    */

    public function map($pattern, $callable, $params=array(), $methods=array()){

        if( $pattern[0] != "/" ) {
            $pattern = "/$pattern";
        }
        //Log::debug("Using pattern: $pattern");

        $realParams = new Parameters($params);
        $realParams->merge($this->_mapParams);

        $this->_routes[$pattern] = new Route($pattern, $this->_mapUri, $callable, $realParams, $methods);

        if(! is_callable($callable) ) {
            $this->_pages[$pattern]=$callable;
            $this->_reverse_routes[$callable]=$pattern;
        }
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

    public function getRouteUrl($route) {
        $url = $this->config("url_base");
        if(!$this->config("rewrite")) {
            $url.="/index.php";
        }
        $url.=$this->_reverse_routes[$route];
        return $url;
    }

    public function getRouteParams($route=false) {

        if(!$route) {
            $theRoute = $this->_routes[ $this->_currentPattern ];
        } else {
            $pattern = $this->_reverse_routes[$route];
            $theRoute = $this->_routes[$pattern];
        }

        $result = clone $theRoute->params;
        $result->merge(array( "page" => $theRoute->callable ));
        return $result;
    }

    public function route( $routes=array() ) {

        foreach($routes as $k=>$v) {

            if($k=="*") {
                $this->_config->merge($v);
                continue;
            }

            if(isset($v["page"])) {
                $callable = $v["page"];
                unset($v["page"]);

                if($k==".") {
                    $k="/";
                }

                $this->map($k, $callable, $v);
            }
        }

        Log::debug("Starting route");

        foreach($this->_routes as $route) {
            if ($route->isMatched) {
                $this->_executeRoute($route);
                break;
            }
        }

        if( ! $this->_routeUsed ) {
            $page404='404';
            if( isset( $this->_reverse_routes[$page404] ) ) {
                $pattern = $this->_reverse_routes[$page404];
                $route = $this->_routes[$pattern];
                $this->_executeRoute($route);
            } else {
                header('HTTP/1.0 404 Not Found');
            }
        }
	}


    public function render($name, $params=array(), $isModule=true) {

        if( ( $pos = strpos($name, ":") ) !== false ) {
            $params["type"] = substr($name, $pos+1);
            $name = substr($name, 0, $pos);
        }

        $viewFile = $this->_getViewFile($name, $isModule);
        $hasView = !!$viewFile;

        $controllerFile = $this->_getControllerFile($name, $isModule);
        $hasController = !!$controllerFile;

        Log::debug(
            "Loading " . ($isModule ? 'MODULE' : 'PAGE') . " ( " . $name ." ) " .
                " controller => " . ( $hasController ? "YES" : "NO") .
                " view => " . ( $hasView ? "YES" : "NO" )
        );

        $controller = new Controller( $viewFile, $params, $this );

        if( $hasController ) {
            require_once $controllerFile;

            $class = ucfirst($name);

            $class = str_replace('-','_', $class);
            $class = str_replace('.','_', $class);

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
            //$controller->app = $this;
            $controller->render();
        }
        return true;
    }

    /*
    *	Config
    */

    public function getPath() {
        return $this->config("path");
    }

    public function setPath($path) {
        $this->config("path", $path);
    }

    public function getUrl( $base=true ) {
    	if($base) {
    		return $this->config("url_base");
    	} else {
    		return $this->config("url");
    	}
    }

    public function config($key, $value=false) {
        if(!$value) {               
            if(is_object($key)){
                $key=(array)$key;
            }            
            if(is_array($key)){
                $this->_config->merge($key);
            } else {    //Getter            
                return $this->_config[$key];
            }
        } else {
            $this->_config[$key]=$value;
        }
    }

	/*
    * Dependencies Objects
    */

    public function getUri(){
    	return $this->_uri;
    }

    public function getRequest(){
    	return $this->_request;
    }

    public function getResponse(){
    	return $this->_response;
    }
   
    /*
    * Private members
    */

    protected static $_instances = array();

    protected $_uri = null;

    protected $_routes = array();
    protected $_routeUsed = false;

    protected $_pages = array();
    protected $_reverse_routes = array();

    protected $_currentPattern = null;

    protected $_config = null;

    private function __construct($name="") {

        $defaults = array(

            "rewrite"           => true,

            "path"              => "",

            "pages_path"        => "pages",
            "modules_path"      => array(
                BAOBAB_PATH,
                "modules"
            ),
            "controllers_path"   => array(
                BAOBAB_PATH . "/controllers",
                "controllers"
            ),
            "layouts_path"   => array(
                "layouts"
            ),
        );
        
        $this->_config = new Parameters($defaults);
        $this->config("name", $name);

        $baobabPath = dirname ( dirname(__FILE__) );
        $_path = dirname(dirname(dirname( $baobabPath ) ) );
        $this->config("path", $_path);

        $this->_response = new Response();
		$this->_request = Request::getInstance();
        $this->_uri = new Uri();

        Log::debug("URI: ");
        Log::debug($this->_uri);
        $this->config("url", $this->_uri->toString());

        $path = $this->_uri["path"];
        if( $pos = strpos($path, "index.php") ){
            $path = substr($path, 0, $pos);
        }

        $uri = clone $this->_uri;
        $urlBase = $uri->toString(array("port"=> "", "path"=>$path, "query"=>"", "fragment"=>""));
        $this->config("url_base", $urlBase);

	    Log::debug("URL Base: " . $urlBase);
        
        $query = $this->_uri["query"];

        //Remove index.php -> no rewrite mode
        if( strpos($query, "index.php") === 0 ){
            $query = substr( $query, strlen("index.php"));
        }

        //Convert query to params
        $this->_mapParams=array();
        if( ( $pos = strpos($query, "?") ) !== false ){

            $q_string = substr( $query, $pos+1 );

            $output_array = array();
            parse_str($q_string, $output_array );

            $this->_mapParams = $output_array;

            $query = substr( $query, 0, $pos);
        }

        $this->_mapUri = "/" . $query;
        Log::debug("Map Uri: ". $this->_mapUri );
    }

    protected function _executeRoute($route){

        $this->_routeUsed = true;
        $params = $route->params;
        Log::debug("Route params:");
        Log::debug($params);
        $callable = $route->callable;
        if(!$callable) {
            Log::warn("Not callable or controller for this map: ". $route->pattern );
        }

        if(is_callable($callable)) {
            $response=$callable($params);
            if( $response instanceof Response ) {
            	$response->apply();
            } else {
            	$this->_response->apply();
            }
        } else {    //Load Controller
            $this->_currentPattern = $route->pattern;
            $layout = isset($route->params["layout"])?$route->params["layout"]: $this->config("layout");
            if($layout) {
                $layoutFile = $this->_getLayoutFile($layout);
                $hasLayout = !!$layoutFile;
                Log::debug("Layout request => $layout ");
                if( $hasLayout ) {
                    Log::debug("Using Layout File => $layoutFile ");
                    $controller = new Controller( $layoutFile, $params, $this );
                    $controller->setVar("page", $route->callable);
                    $controller->render();
                    $this->_routeUsed=true;
                    return;
                }
            }
            $this->_response->apply();
            $this->_routeUsed = $this->render($route->callable, $route->params, false);
        }
    }


    protected function _getLayoutFile($name) {
        $paths = $this->config("layouts_path");

        if(!is_array($paths)) {
            $paths = array( $paths );
        }

        foreach( array_reverse($paths) as $path) {
            $viewFile = $path . "/" . $name . ".l" . ".php";
            if(file_exists($viewFile)){
                Log::debug( "Using layout file : ( " . $viewFile ." ) " );
                return $viewFile;
            }
        }

        return false;
    }

    protected function _getViewFile($name, $isModule=true) {
        $paths = $isModule ? $this->config("modules_path") : $this->config("pages_path") ;

        if(!is_array($paths)) {
            $paths = array( $paths );
        }

        foreach( array_reverse($paths) as $path) {
            $viewFile = $path . "/" . $name . ( $isModule ? ".m" : '' ) . ".php";
            if(file_exists($viewFile)){
                Log::debug( "Using view file : ( " . $viewFile ." ) " );
                return $viewFile;
            }
        }

        return false;
    }

    protected function _getControllerFile($name, $isModule=true) {
        $paths = $this->config("controllers_path");

        if(!is_array($paths)) {
            $paths = array( $paths );
        }

        foreach( array_reverse($paths) as $path) {
            $controllerFile = $path . "/" . $name . ".". ($isModule ? 'm' : '') ."c.php";
            if(file_exists($controllerFile)){
                Log::debug( "Using controller file : ( " . $controllerFile ." ) " );
                return $controllerFile;
            }
        }

        return false;
    }
}






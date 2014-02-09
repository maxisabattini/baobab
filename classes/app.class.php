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
require_once "router.class.php";
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
    public function getRouteUrl( $routeName ) {

        $parameters=array();
        $pieces=explode( "?", $routeName);
        if( $pieces && count($pieces) > 1 ) {
            $routeName=$pieces[0];
            parse_str( $pieces[1] , $parameters );
        }

        $route = $this->_router->getRouteByName($routeName);

        $url = $this->config("url_base");
        if(!$this->config("rewrite")) {
            $url.="/index.php";
        }

        if( $route ) {
            $url .= $route->makeUrl($parameters);
        }
        return $url;
    }

    public function getRouteParams($routeName=false) {        

        $parameters = new Parameters();

        if($routeName) {
            $route = $this->_router->getRouteByName($routeName);
            $parameters->merge( $route->parameters );           
        } else {
            //Current Used Route
            $matches = $this->_router->getLatestMatched();
            if( $matches ) {         
                $parameters->merge($matches[0]->parameters);
            }             
        }

        return $parameters;                    
    }

    public function route( $routes=array() ) {

        foreach($routes as $k=>$v) {

            if($k=="*") {
                $this->_config->merge($v);
                continue;
            }

            if(isset($v["page"])) {
                $action = $v["page"];                

                if($k==".") {
                    $k="/";
                }

                $this->_router->map($k, $action, $action, $v);                
            }
        }

        Log::debug("Starting route");

        $matches = $this->_router->getMatched(true);        
        if( $matches ) {
            $this->_executeRoute($matches[0]);
        } else {
            //404 here
            $page404='404';
            $route=$this->_router->getRouteByName($page404);
            if($route) {
                $this->_executeRoute($route);
            } else {
                header('HTTP/1.0 404 Not Found');
            }
        }
	}

    public function redirect($url) {
        if(headers_sent()) {
            $string = '<script type="text/javascript">';
            $string .= 'window.location = "' . $url . '"';
            $string .= '</script>';

            echo $string;
        } else {
            if (isset($_SERVER['HTTP_REFERER']) && ($url == $_SERVER['HTTP_REFERER']))
                header('Location: '.$_SERVER['HTTP_REFERER']);
            else
                header('Location: '. $url);
        }
        exit;
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

    public function getRouter(){
        return $this->_router;
    }

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

    

    protected $_routeUsed = false;
    protected $_currentPattern = null;

    protected $_config = null;
    protected $_uri = null;
    protected $_router = null;
    protected $_request = null;
    protected $_response = null;

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

        $this->_router = new Router($this->_mapUri);
    }

    protected function _executeRoute($route){

        $this->_routeUsed = true;

        Log::debug("Route params:");
        Log::debug($route->parameters);
        $callable = $route->action;
        if(!$callable) {
            Log::warn("Not callable or controller for this map: ". $route->pattern );
        }

        if(is_callable($callable)) {
            $response=$callable($route->parameters);
            if( $response instanceof Response ) {
            	$response->apply();
            } else {
            	$this->_response->apply();
            }
        } else {    //Load Controller
            $this->_currentPattern = $route->pattern;
            $layout = isset($route->parameters["layout"])?$route->parameters["layout"]: $this->config("layout");
            if($layout) {
                $layoutFile = $this->_getLayoutFile($layout);
                $hasLayout = !!$layoutFile;
                Log::debug("Layout request => $layout ");
                if( $hasLayout ) {
                    Log::debug("Using Layout File => $layoutFile ");
                    $controller = new Controller( $layoutFile, $route->parameters, $this );
                    $controller->setVar("page", $route->action);
                    ob_start();
                    $controller->render();
                    echo ob_get_clean();
                    $this->_routeUsed=true;
                    return;
                }
            }
            $this->_response->apply();
            $this->_routeUsed = $this->render($route->action, $route->parameters, false);
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

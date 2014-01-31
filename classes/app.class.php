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

class Parameters implements \ArrayAccess {

	private $_container = null;

	public function __construct($default=array()){
		$this->_container= is_array( $default ) ? $default : array();
	}

	/*
	 * Array Access
	*/

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
		if(!isset($this->_container[$offset]) ) {
			Log::warn("Param not set: " . $offset);
			return null;
		} else {
			return $this->_container[$offset];
		}
	}

	/*
	 * Object Access
	*/

	public function __get($key){
		return $this->offsetGet($key);
	}

	public function __set($key, $value){
		$this->offsetSet($key, $value);
	}

	/*
	 * Common
	*/

	public function merge( $input ){
		if( is_object($input) ) {
			$this->_container=array_merge($this->_container, (array) $input);
		}
		if(is_array($input)) {
			$this->_container=array_merge($this->_container, $input);
		}
	}

	public function exists(){}

	public function toArray( $recursive = false ){
		return $this->_container;
	}

	public function toObject( $recursive = false ){
		return (object) $this->_container;
	}

	public function toString($format = 'JSON'){
		//TODO:
	}
}

class Uri extends Parameters {

    public function __construct() {
    	parent::__construct();

        $this->scheme='http';
        if ( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $this->scheme='https';
        }
        $this->host=$_SERVER['HTTP_HOST'];
        $this->port=$_SERVER["SERVER_PORT"];

        $self = $_SERVER['PHP_SELF'];
        $uri = $_SERVER['REQUEST_URI'];
        $pos = strrpos($self, "/");
        $this->user="";
        $this->pass="";
        $this->path=substr($self, 0, $pos+1);
        $this->query=(string) substr($uri, $pos+1);      //?adasd
        $this->fragment="";  //#adas    only with js
    }

    public function toString($overrides=array()) {        
        $this->merge($overrides);

        $pageURL = $this->scheme ."://".$this->host . $this->path . $this->query;
        if( $this->port && $this->port!=80  ) {
            $pageURL .= ":" . $this->port;
        }
        if( substr( $pageURL, -1) === "/" ) {
            $pageURL = substr( $pageURL, 0, strlen($pageURL) - 1 );
        }

    	Log::info("Uri::toString()");
	    Log::debug($pageURL);
        return $pageURL;
    }
}

class Route {

    public $pattern;
    public $callable;
    public $params;

    public $conditions = array();

    public $methods = array();

    public $isMatched = false;

    function __construct($pattern, $url, $callable=null, $params=array(), $methods=array()){

        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->methods = $methods;

        $this->params = $params;

        $p_names = array(); $p_values = array();

        preg_match_all('@:([\w]+)@', $pattern, $p_names, PREG_PATTERN_ORDER);
        $p_names = $p_names[0];

        $conditions=$this->conditions;
		$regex_url = function($matches) use ($conditions) {
	    	$key = str_replace(':', '', $matches[0]);
	    	if (array_key_exists($key, $conditions)) {
	    		return '('.$conditions[$key].')';
	    	}
	    	else {
	    		return '([a-zA-Z0-9_\+\-%]+)';
	    	}
	    };

        $url_regex = preg_replace_callback('@:[\w]+@', $regex_url, $pattern);
        $url_regex .= '/?';

        if (preg_match('@^' . $url_regex . '$@', $url, $p_values)) {
            array_shift($p_values);

            foreach($p_names as $index => $value) $this->params[substr($value,1)] = urldecode($p_values[$index]);

            //foreach($target as $key => $value) $this->params[$key] = $value;

            $this->isMatched = true;
        }

        unset($p_names); unset($p_values);
    }


    public function matches($uri){}
    
    /*
    function regex_url($matches) {
    	$key = str_replace(':', '', $matches[0]);
    	if (array_key_exists($key, $this->conditions)) {
    		return '('.$this->conditions[$key].')';
    	}
    	else {
    		return '([a-zA-Z0-9_\+\-%]+)';
    	}
    }
    */
}

class Request {

	public function isPost() {
		return $this->_method=="post";
	}

	public function isGet() {
		return $this->_method=="get";
	}

	public function isPut() {
		return $this->_method=="put";
	}

	public function isDelete() {
		return $this->_method=="delete";
	}

	public function isSecure(){
		if ( isset($_SERVER['HTTPS']) ) {
	        if ( 'on' == strtolower($_SERVER['HTTPS']) )
	            return true;
	        if ( '1' == $_SERVER['HTTPS'] )
	            return true;
	    } elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
	        return true;
	    }
	    return false;
	}

	public function isAjax() {
		return $_isAjax;
	}

	public function getMethod(){
		return strtoupper($this->_method);
	}

	public function getVar($name) {
		return isset($_REQUEST[$name])?$_REQUEST[$name]:null;
	}

	public function getHeader($name) {		
		return isset($this->_headers[$name])?$this->_headers[$name]:null;	
	}

	public function hasFiles(){
		//TODO:
		return false;
	}

	public function getFiles(){
		//TODO:
		return false;
	}

	public function getServerAddress() {
		//TODO:
		return false;	
	}

	public function getClientAddress() {
		//TODO:
		return false;	
	}

	public function getAcceptableContent() {
		//TODO:
		return false;	
	}

    public static function getInstance() {
        if(!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

	protected static $_instance;

	private $_method='';
	private $_isAjax=false;
	private $_headers=array();

	private function __construct() {

		//Method
		$this->_method = strtolower($_SERVER['REQUEST_METHOD']);
		$this->_isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

		//Headers		
		foreach ($_SERVER as $key => $value) {
 		   	if (strpos($key, 'HTTP_') === 0) {
 		   		$_key=str_replace('_', ' ', substr($key, 5));
				$this->_headers[$_key]=$value;
 		   	}
 		}        
    }

}

class Response {

	public function setHeader($name, $value) {
		$this->_headers[$name]=$value;
	}

	public function setExpires($timestamp, $zone="GMT") {
		$this->setHeader("Expires",gmdate("D, d M Y H:i:s $zone", $timestamp) );
	}

	public function setContent($type="application/octet-stream") {
		$this->setHeader("Content-Type", $type );
	}

	public function redirect($url) {
		$this->apply();
		header("location: $url");
	}

	public function apply($response=null) {
		/*
		for( $this->_headers as $key => $value ) {
			header("$key: $value");
		}
		*/
	}

	private $_headers=null;

	public function __construct() {
		$this->_headers=new Parameters();
	}

}


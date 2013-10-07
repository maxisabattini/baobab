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

    public function map($pattern, $callable, $params=array(), $methods=array()){

        if( $pattern[0] != "/" ) {
            $pattern = "/$pattern";
        }
        //Log::debug("Using pattern: $pattern");

        $realParams = new Params($params);
        $realParams->merge($this->_mapParams);

        $this->_routes[$pattern] = new Route($pattern, $this->_mapUri, $callable, $realParams, $methods);

        if(! is_callable($callable) ) {
            $this->_pages[$pattern]=$callable;
            $this->_r_pages[$callable]=$pattern;
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

    // View/Controller management

    public function render($name, $params=array(), $isModule=true) {

        if(!is_array($params)){
            $params=array();
        }

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
            $controller->app = $this;
            $controller->render();
        }
        return true;
    }

    // Pages management

    public function pageUrl($page) {
        $url = $this->config("url_base");
        if(!$this->config("rewrite")) {
            $url.="/index.php";
        }
        $url.=$this->_r_pages[$page];
        return $url;
    }

    public function pageParams($page=false) {

        if(!$page) {
            $route = $this->_routes[ $this->_currentPattern ];
        } else {
            $pattern = $this->_r_pages[$page];
            $route = $this->_routes[$pattern];
        }

        $result = clone $route->params;
        $result->merge(array( "page" => $route->callable ));
        return $result;
    }

    public function route( $routes = array() ) {

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
            if( isset( $this->_r_pages[$page404] ) ) {
                $pattern = $this->_r_pages[$page404];
                $route = $this->_routes[$pattern];
                $this->_executeRoute($route);
            } else {
                header('HTTP/1.0 404 Not Found');
            }
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

    // Deprecated
    //

    public function getPath() {
        return $this->config("path");
    }

    public function setPath($path) {
        $this->config("path", $path);
    }

    public function getUrlParts(){
        return $this->_url;
    }    

    public function getUrl() {
        return $this->config("url");
    }


    public function getUrlBase() {
        return $this->config("url_base");
    }

    // END Deprecated
    //

    // Private members

    protected static $_instances = array();

    protected $_url= array();

    protected $_routes = array();
    protected $_routeUsed = false;

    protected $_pages = array();
    protected $_r_pages = array();

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
        $this->_config = new Params($defaults);
        $this->config("name", $name);

        $baobabPath = dirname ( dirname(__FILE__) );
        $_path = dirname(dirname(dirname( $baobabPath ) ) );
        $this->config("path", $_path);

        $this->_url = new Url();

        Log::debug("URL Base: ");
        Log::debug($this->_url);

        $_url = $this->_url->toString();

        $this->config("url", $_url);

        $path = $this->_url["path"];
        if( $pos = strpos($path, "index.php") ){
            $path = substr($path, 0, $pos);
        }

        $_urlBase = $this->_url->toString(array("port"=> "", "path"=>$path, "query"=>"", "fragment"=>""));
        $this->config("url_base", $_urlBase);

	    //Log::debug("URL Base: " . $_urlBase);

        $url = $this->_url;
        $query = $url["query"];

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
            $callable($params);
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
        $this->callable = $callable;
        $this->methods = $methods;

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

class Params implements \ArrayAccess {

    private $_container = null;

    public function __construct($default=array()){
        $this->_container= is_array( $default ) ? $default : array();
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
        if(!isset($this->_container[$offset]) ) {
            Log::warn("Config/Param not set: " . $offset);
            return null;
        } else {
            return $this->_container[$offset];
        }
    }

    public function merge($array){
        if(is_array($array)) {
            $this->_container= array_merge($this->_container, $array);
        }
    }

    public function toArray(){
        return $this->_container;
    }
}

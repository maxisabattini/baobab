<?php namespace baobab;

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
require_once "language.class.php";

class App {

    /**
     * Get an instance of App by name.
     *
     * @param string $options App instance initial options
     * @return \baobab\App Default App instance.
     */
    public static function getInstance($options=array()) {		
		$name='default';
		if(isset($options['name'])) {
			$name=$options['name'];
		}	
        if( ! isset(self::$_instances[$name]) ) {
            self::$_instances[$name] = new self($name, $options);
        }
        return self::$_instances[$name];
    }


    /*
    |--------------------------------------------------------------------------
    | Routes Management
    |--------------------------------------------------------------------------
    |
    */


    /**
     * Return a url of given route name
     *
     * @param $routeName
     * @return string
     */
    public function getRouteUrl( $routeName ) {

        $parameters=array();
        $pieces=explode( "?", $routeName);
        if( $pieces && count($pieces) > 1 ) {
            $routeName=$pieces[0];
            parse_str( $pieces[1] , $parameters );
        }
		
		$code=$this->_language->getCode();
		if( $this->config("languages_default") != $code ) {
			$route = $this->_router->getRouteByName("$routeName:$code");
			if(!$route) {
				$route = $this->_router->getRouteByName($routeName);
			}
		} else {
			$route = $this->_router->getRouteByName($routeName);
		}

        $url = $this->config("url_base");
        if(!$this->config("rewrite")) {
            $url.="/index.php";
        }

        if( $route ) {
            $url .= $route->makeUrl($parameters);
        }
        return $url;
    }

    /**
     * Return parameter of given route name, if not route name provided return the
     * current used route.
     *
     * @param bool $routeName
     * @return \baobab\Parameters
     */
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

        foreach($routes as $pattern=>$v) {

            if($pattern=="*") {
                $this->_config->merge($v);
                continue;
            }

            if(isset($v["page"])) {
                $action = $v["page"];
				$name=$action;

                if($pattern==".") {
                    $pattern="/";
                }
				
				//Language mapping
				if( isset($v["lang"]) ) {
					$name = $action . ":". $v["lang"];
				}

                $this->_router->map($pattern, $action, $name, $v);                
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
				Log::debug("Throwing 404 page");
                $this->_executeRoute($route);
            } else {
				Log::debug("Throwing 404");
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

    /**
     * Render a module or page
     *
     * @param $name Module/Page name to render
     * @param array $params
     * @param bool $isModule
     * @return bool If was renderer
     */
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
    |--------------------------------------------------------------------------
    | Config
    |--------------------------------------------------------------------------
    |
    */


    /**
     * Get the App path.
     *
     * @return string
     */
    public function getPath() {
        return $this->config("path");
    }

    /**
     * Set the app path.
     *
     * @param string $path
     */
    public function setPath($path) {
        $this->config("path", $path);
    }

    /**
     * Get the app url, by default is a base url.
     * If base false is provided, return a full url.
	 * If base is a string, this its used like custom base, mean 'url_statics'
	 * 'url_images'.
     *
     * @param bool $base
     * @return string
     */
    public function getUrl( $base=true ) {			
    	if($base) {
			if( is_bool($base) ) {
				return $this->config("url_base");
			} else {
				$customBase=$this->config("url_$base");
				if(!$customBase) {
					$customBase=$this->config("url_base");
				}
				return $customBase;
			}
    	} else {
    		return $this->config("url");
    	}
    }

    /**
     * Config access function.
     * If only a key is provided return a value.
     * If is provided value, set it.
     *
     * @param string $key Key to find
     * @param bool|mixed $value Value to set
     * @return mixed
     */
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
    |--------------------------------------------------------------------------
    | Dependencies Objects
    |--------------------------------------------------------------------------
    |
    */

    /**
     * Get default app Router.
     *
     * @return Router
     */
    public function getRouter(){
        return $this->_router;
    }

    /**
     * Get a current request Uri
     *
     * @return Uri
     */
    public function getUri(){
    	return $this->_uri;
    }

    /**
     * Get a current Request
     *
     * @return Request
     */
    public function getRequest(){
    	return $this->_request;
    }

    /**
     * Get the app response
     *
     * @return Response
     */
    public function getResponse(){
    	return $this->_response;
    }

    /**
     * Get the app language
     *
     * @return Language
     */
    public function getLanguage(){
    	return $this->_language;
    }


    /*
    |--------------------------------------------------------------------------
    | Private members
    |--------------------------------------------------------------------------
    |
    */

    /**
     * Array of App
     *
     * @var \baobab\App[]
     */
    protected static $_instances = array();
    

    protected $_routeUsed = false;
    protected $_currentPattern = null;

    /**
     * Config holder
     *
     * @var \baobab\Parameters
     */
    protected $_config = null;

    /**
     * Uri object of current request
     *
     * @var \baobab\Uri
     */
    protected $_uri = null;

    /**
     * Router for this app instance
     *
     * @var \baobab\Router
     */
    protected $_router = null;

    /**
     * Request used for this app
     *
     * @var \baobab\Request
     */
    protected $_request = null;


    /**
     * Default response
     *
     * @var \baobab\Response
     */
    protected $_response = null;


    /**
     * Default language
     *
     * @var \baobab\Language
     */
    protected $_language = null;


    /**
     * Create a new App.
     *
     * @param string $name An optional App name.
     */
    private function __construct($name="", $options=array()) {        

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
            "languages_path"   => array(
                "languages"
            ),

            "languages_default" => "en_US",
        );
        
        $this->_config = new Parameters($defaults);
		
		//Default name
		$this->config("name", $name);
		
		//Default path
        $baobabPath = dirname ( dirname(__FILE__) );
        $_path = dirname(dirname(dirname( $baobabPath ) ) );
        $this->config("path", $_path);
		
		//Custom options 
		$this->_config->merge($options);


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
		$this->_language=new Language( $this->config("languages_default"), $this->config("languages_path"));
    }

    protected function _executeRoute($route){

        $this->_routeUsed = true;

        Log::debug("Route params:");
        Log::debug($route->parameters);

        if(isset($route->parameters["lang"])){
            $this->_language=new Language($route->parameters["lang"], $this->config("languages_path"));
        }
		
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

                    $sanitize_output = function($buffer) {

                        $search = array(
                            '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
                            '/[^\S ]+\</s',  // strip whitespaces before tags, except space
                            '/(\s)+/s'       // shorten multiple whitespace sequences
                        );

                        $replace = array(
                            '>',
                            '<',
                            '\\1'
                        );

                        $buffer = preg_replace($search, $replace, $buffer);

                        return $buffer;
                    };

                    if( $this->config("packed_resources") ) {
                        echo $sanitize_output( ob_get_clean() );
                    } else {
                        echo ob_get_clean();
                    }
                    
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
	
		$suffixes=array();		
		$code=$this->_language->getCode();
		if( $this->config("languages_default") != $code ) {
			$suffixes[]=".$code";
		}		
		$suffixes[]="";
		
		Log::debug( "Using view file suffixes : " );
		Log::debug( $suffixes );
		
        if(!is_array($paths)) {
            $paths = array( $paths );
        }

        foreach( array_reverse($paths) as $path) {		
			foreach($suffixes as $suffix) {				
				$viewFile = $path . "/" . $name . $suffix . ( $isModule ? ".m" : '' ) . ".php";
				if(file_exists($viewFile)){
					Log::debug( "Using view file : ( " . $viewFile ." ) " );
					return $viewFile;
				}
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

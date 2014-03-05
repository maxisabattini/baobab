<?php

namespace baobab;

class Router {

    protected $_routes_by_pattern = array();
    protected $_routes_by_name = array();
    protected $_latestMatched = array();

	protected $_url = null;

    public function __construct($url){
    	$this->_url = $url;
    }

    public function get($pattern, $callable ){
        $this->map($pattern, $callable, null, array("get"));
    }

    public function post($pattern, $callable ){
        $this->map($pattern, $callable, null, array("post"));
    }

    public function put($pattern, $callable ){
        $this->map($pattern, $callable, null, array("put"));
    }

    public function patch($pattern, $callable ){
        $this->map($pattern, $callable, null, array("patch"));
    }

    public function delete($pattern, $callable ){
        $this->map($pattern, $callable, null, array("delete"));
    }

    public function map($pattern, $action, $name=null, $params=array(), $methods=array("get")){

        if( $pattern[0] != "/" ) {
            $pattern = "/$pattern";
        }

		$route = new Route($pattern, $action, $name, $params, $methods);
		
        $this->_routes_by_pattern[$pattern] = $route;
		$this->_routes_by_name[$route->name]= $route;
    }

    public function getRouteByName($name) {
    	return isset( $this->_routes_by_name[$name] ) ? $this->_routes_by_name[$name] : false;
    }

    public function getMatched( $onlyFirst=false ) {

    	$result=array();
    	foreach($this->_routes_by_pattern as $pattern => $route ) {
            if ($route->matches( $this->_url )) {
            	$result[]=$route;
            	if($onlyFirst) {
            		break;
            	}
            }
        }
        $this->_latestMatched=$result;
        return $result;
    }

    public function getLatestMatched() {
    	return $this->_latestMatched;
    }
}

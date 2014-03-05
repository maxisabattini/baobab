<?php

namespace baobab;

class Route {

    public $pattern;

    public $action;

    public $name;

    public $parameters;

    public $methods;

	public $lastUrlMatched = null;
    public $isMatched = false;

    /**
    *
    * Create a new Route instance.
    *
    * @param  string   $pattern
    * @param  string|callable   $action
    * @param  array   $params
    * @param  array   $methods
    */
    public function __construct($pattern, $action=null, $name=null, $parameters=array(), $methods=array()){

        $this->pattern = $pattern;
        $this->action = $action;
        $this->name = $name;
        if(!$name) {
            $this->name = $pattern;
        }

		$this->parameters = $parameters;

        $this->methods = $methods;        
    }

    public function matches($url){

		$this->isMatched = false;

        $variables_regex='@{([\w]+)}@';
		$variables = array();
        preg_match_all($variables_regex, $this->pattern, $variables, PREG_PATTERN_ORDER);
        $variables = $variables[1];
        
		$value_regex='([a-zA-Z0-9_\+\-%]+)';
		$url_regex = preg_replace($variables_regex, $value_regex, $this->pattern);
		$url_regex .= '/?';

		$values = array();
		if (preg_match('@^' . $url_regex . '$@', $url, $values)) {

			$this->isMatched = true;
			$this->lastUrlMatched = $url;

			array_shift($values);
            foreach($variables as $i => $varName) {
            	$this->parameters[$varName]=urldecode( $values[$i] );
            }
		}

		unset($variables); unset($values);

		return $this->isMatched;
    }

    public function makeUrl($parameters) {

    	$url=$this->pattern;
    	foreach ($parameters as $key => $value) {
    		$url=str_replace('{'.$key.'}', $value, $url);
    	}

    	return $url;
    }
}


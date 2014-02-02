<?php

namespace baobab;

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
    
}


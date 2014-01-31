<?php

namespace baobab;

class Controller {

	protected $_params;
    protected $_view;
    //protected $vars = array();

    public $app=null;

	public function __construct( $view, $params = array(), $app=null ) {
		$this->_params = new Parameters($params);
		$this->_view = $view;

        if(!$app) {
            $this->app=App::getInstance();
        } else {
            $this->app=$app;
        }
    }

    public function setView($view){
		$this->_view = $view;
    }

    public function render($name="", $params=array(), $isModule=true) {

        if($name) {
            Log::debug("Render on Controller : $name");
            $viewParams = new Params($this->getVars());
            $viewParams->merge($params);
            $this->app->render($name, $viewParams->toArray(), $isModule);
            return;
        }


		//Load Description file
		$descFile = $this->getViewDescFile();
		if(file_exists($descFile)) {
			$vars = array();
			include $descFile;
			foreach( $vars as $k => $v ) {		
				if( is_int($k) ) {
					$$v = false;
				} else {
					$$k = $v;
				}
			}
		}
		$this->_params->merge($params);

        //Expose vars
        foreach( $this->_params->toArray() as $fieldName => $fieldValue ) {
            $$fieldName = $fieldValue;
        }

        //Render
        if( $this->_view ) {
            include $this->_view;
        }
    }

    public function setVar($name, $value) {
        $this->_params[$name]=$value;
    }

    public function getVar($name) {
        return $this->_params[$name];
    }

    public function getVars(){
        return $this->_params->toArray();
    }
	
	private function getViewDescFile() {	
		$baseFile = substr($this->_view, 0, strlen( $this->_view ) - 4 );		
		return $baseFile . ".desc.php";
	}
}

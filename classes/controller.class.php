<?php

namespace baobab;

class Controller {

	protected $params;
    protected $view;
    protected $vars = array();

    public $app=null;

	public function __construct( $view, $params = array(), $app=null ) {
		$this->params = $params;
		$this->view = $view;

        if(!$app) {
            $this->app=App::getInstance();
        } else {
            $this->app=$app;
        }
    }

    public function setView($view){
		$this->view = $view;
    }

    /*
    public function exposeVarsAsGlobals() {
        //Expose vars
        foreach( $this->vars as $fieldName => $fieldValue ) {
            global $$fieldName;
            $$fieldName = $fieldValue;
        }
    }
    */

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

        //Expose vars
        foreach( $this->params as $fieldName => $fieldValue ) {
            $$fieldName = $fieldValue;
        }
        foreach( $this->vars as $fieldName => $fieldValue ) {
            $$fieldName = $fieldValue;
        }
		
        //Render
        if( $this->view ) {
            include $this->view;
        }
    }

    public function setVar($name, $value) {
        $this->vars[$name]=$value;
    }

    public function getVar($name) {
        return $this->vars[$name];
    }

    public function getVars(){
        return $this->vars;
    }
	
	private function getViewDescFile() {	
		$baseFile = substr($this->view, 0, strlen( $this->view ) - 4 );		
		return $baseFile . ".desc.php";
	}
}

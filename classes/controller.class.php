<?php

namespace baobab;

class Controller {

	protected $_params;
    protected $_view;
    
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

    public function _($key, $placeholders=array()){
        return $this->app->getLanguage()->get($key, $placeholders);
    }

    public function setView($view){
		$this->_view = $view;
    }

    public function render($name="", $params=array(), $isModule=true) {

        if($name) {
            Log::debug("Render on Controller : $name");
            $viewParams = new Parameters($this->getVars());
            $viewParams->merge($params);
            $this->app->render($name, $viewParams->toArray(), $isModule);
            return;
        }

        //ob_start();

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
        Log::info( "To expose params");
        Log::debug( $this->_params->toArray() );

        foreach( $this->_params->toArray() as $fieldName => $fieldValue ) {
            $$fieldName = $fieldValue;
        }

        //Render
        if( $this->_view ) {
            Log::debug("Including View : " .  $this->_view );
            include $this->_view;
        }

        //echo ob_get_clean();
    }

    public function setVar($name, $value) {
        $this->_params[$name]=$value;
    }

    public function setVars($array) {
        $this->_params->merge($array);
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

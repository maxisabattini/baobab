<?php

namespace baobab;

/*
 * CMD mode;                WEB mode;
 *
 * param=
 * -param=           =>      &param=1
 *
 * --param=value    =>      &param=value
 */
class Cli {

    protected $_out;
    protected $_in;

    protected $_name = "Cli";

    protected $_silent = false;
    protected $_params = false;

    public function __construct(){
        if( BAO_CMD ) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {                    
                $this->_out = new CliOutputCmd();
            } else {
                $this->_out = new CliOutputCmdLinux();
            }
        } else {
            $this->_out = new CliOutputWeb();
        }
        if($this->getParam("silent")) {
			$this->_silent=true;
		}
    }

    public function __call($method, $args) {    	
    	if( substr($method, 0, strlen("write")) == "write" ) {
    		$tag = substr($method, strlen("write") );
    		$value = isset($args[0]) ? $args[0] : "" ;
    		$arguments = isset($args[1]) ? $args[1] : "" ;
    		$this->_out->write($tag, $value, $arguments);
    	}
        if( substr($method, 0, strlen("out")) == "out" ) {
    		$tag = substr($method, strlen("out") );
    		$value = isset($args[0]) ? $args[0] : "" ;
    		$arguments = isset($args[1]) ? $args[1] : "" ;
    		$this->_out->write($tag, $value, $arguments);
    	}
    }


    /**
     * Get all parameters
     *
     * @return array
     */
    protected function getParams(){
        $this->getParam("");
        return $this->_params;
    }

    /**
     * Get a parameter with name
     *
     * @param $name
     * @return mixed
     */
    protected function getParam($name) {
        if( BAO_CMD ) {
            if( ! is_array( $this->_params ) ) {
                global $argv;
                $this->_params = array();

                foreach($argv as $arg) {
                    $param = $arg;

                    if( strlen($param) > 1 && $param[0] == '-') {
                       $param = substr($param, 1);
                    }
                    if( strlen($param) > 1 && $param[0] == '-') {
                        $param = substr($param, 1);
                    }

                    $parts = explode("=", $param);
                    if( count($parts)> 1 ) {
                        //has value
                        $this->_params[ $parts[0] ] = $parts[1];
                    } else {
                        //not has value
                        $this->_params[ $param ] = false;
                    }
                }
            }
            if( isset($this->_params[$name]) ) {
                return $this->_params[$name];
            }
            return null;
        } else {
            //Todo: review this
            return isset($_REQUEST[$name])?$_REQUEST[$name]:null;
        }
    }

    /**
     * Check if is a paramter passed
     *
     * @param $name
     * @return bool
     */
    protected function hasParam($name) {
        return $this->getParam($name) !== null;
    }   

	/**
	 * Execute the app
     *
	 */    
    public function execute(){
        if(!$this->_silent) {
            $this->writeHeader($this->name);
        }            

        //Call pre scripts
        $this->executeReal();
        //Call post scripts

        if(!$this->_silent) {
            $this->writeFooter();
        }            
    }    

	/**
	 * Real Execute function, subclasses must override it
	 *
	 */    
    protected function executeReal(){
        $this->out("Override me!");
    }
    
}


interface CliOutput {	
	public function write( $tag, $value, $args=array());
}

class CliOutputDefault implements CliOutput {	

	public function write( $tag, $value, $args=array()){		
	}

	public function outNl(){
		$this->out("\n");
	}

	public function out($string, $args=array() ){
		if( $args ) {
			print vsprintf($string, $args );
		} else {
			print $string;
		}
	}    
    
    public function outObject($obj ,$hideEmpty=false){
		$vars=false;
		if( is_object($obj) ) {
			$vars = get_object_vars($obj);			
		}		
		if( is_array($obj) ) {
			$vars = $obj;			
		}		
		if(!$vars){
			return;
		}
        
        $maxStr=0;
        foreach($vars as $i => $v){
            $maxStr = strlen($i) > $maxStr ? strlen($i) : $maxStr;
        }
        foreach($vars as $i => $v){
            $s=$v;
            if($hideEmpty&&!$s) {
                continue;
            }

            if(is_object($v) || is_array($v)) {
                $s=print_r($v, true);
            }
            
            $this->out( str_pad($i, $maxStr, " ", STR_PAD_LEFT) . ": " . $s );
            $this->outNl();
        }
    }

    public function outObjectList($objects, $hideEmpty=false ){
        $i=1;
        foreach($objects as $o) {
            $this->out(str_pad( " $i.row ", 80, "*", STR_PAD_BOTH) );            
			$this->outNl();
            $this->outObject($o, $hideEmpty);
            $i++;
        }
    }    
}

class CliOutputCmd extends CliOutputDefault {
   
    public function write( $tag, $string, $args=array()){

        $values = isset($args["values"])?$args["values"]:array();
        $mode = isset($args["mode"])?$args["mode"]:array();
        
        switch($tag) {

			case "Footer":
				$this->outNl();
                $this->out( str_pad("", strlen($string), "-" ) );								
                $this->outNl();
			break;

            case "Header":
			case "Head":
            case "Title":
                $this->outNl();
				$this->out($string, $values);
                $this->outNl();
                $this->out( str_pad("", strlen($string), "=" ) );				
                $this->outNl();
			break;

			case "Paragraph":
            case "Comment":   
            	$this->outNl();             
            	$this->out("```");
            	$this->outNl();                
		        $this->out($string, $values);
            	$this->outNl();             
            	$this->out("```");
            	$this->outNl();
			break;				

            case "Line":                
                switch( strtolower(trim($mode))) {
                    case "error":                        
                        $this->out("!!! ");
                        break;
                    case "warn":
                        $this->out("!! ");
                        break;
                    case "info":
                        $this->out("! ");
                        break;
                    default:
                        break;
                }                
                $this->out($string, $values);                
                $this->outNl();
			break;				
       
            case "Ok":
                $this->out("[ OK  ] ");
		        $this->out($string, $values);
                $this->outNl();
			break;
        
            case "Error":
                $this->out("[ERROR] ");
		        $this->out($string, $values);
                $this->outNl();
			break;
        
            case "Object":
                $this->outObject($string ,isset($args["hideEmpty"])?$args["hideEmpty"]:false );                
            break;
        
            case "ObjectList":
                $this->outObjectList($string ,isset($args["hideEmpty"])?$args["hideEmpty"]:false );                
            break;
        
			default:
				$this->out($string, $values);
		}        
    }   
}


class CliOutputCmdLinux extends CliOutputDefault {	
	
	public function write($tag, $string, $args=array()){
        
        $values = isset($args["values"])?$args["values"]:array();
        $mode = isset($args["mode"])?$args["mode"]:array();
        
        switch($tag) {
									
			case "Header":
				$this->out("\033[0m");
				$this->out("\033[1m");
				$this->outNl();
				$this->out($string);
				$this->outNl();
				$this->out("\033[0m");
				$this->outNl();
			break;

			case "Footer":
				$this->outNl();
				$this->out("\033[0m");
				$this->outNl();
			break;

			case "Head":
            case "Title":
		        $this->out("\033[0m");
		        $this->out("\033[32m");
		        $this->out("\033[1m");
				$this->out($string, $values);
				$this->out("\033[0m");
				$this->outNl();
			break;

			case "Paragraph":
            case "Comment":                
		        $this->out("\033[0m");
		        $this->outNl();
		        $this->out("\033[32m");
		        $this->out($string, $values);
				$this->out("\033[0m");
				$this->outNl();
			break;				

            case "Line":		        
		        $this->out("\033[0m");               
                switch( strtolower(trim($mode))) {
                    case "error":
                        $this->out("\033[31m");
                        break;
                    case "warn":                        
                        $this->out("\033[35m");
                        break;
                    case "info":
                        $this->out("\033[36m");
                        break;
                    default:
                        break;
                }            

                $this->out($string, $values);
				$this->out("\033[0m");
				$this->outNl();
			break;				
       

            case "Ok":
            	$this->out("\033[0m");		        
                $this->out("\033[36m[ OK  ] ");
                $this->out("\033[0m");
		        $this->out($string, $values);
				$this->out("\033[0m");
				$this->outNl();
			break;				
        
            case "Error":
            	$this->out("\033[0m");		        
                $this->out("\033[31m[ERROR] ");
                $this->out("\033[0m");
		        $this->out($string, $values);
				$this->out("\033[0m");
				$this->outNl();
			break;
        
            case "Object":
                $this->outObject($string ,isset($args["hideEmpty"])?$args["hideEmpty"]:false );                
            break;
        
            case "ObjectList":
                $this->outObjectList($string ,isset($args["hideEmpty"])?$args["hideEmpty"]:false );                
            break;
        
			default:
				$this->out($string, $values);
		}
	}	
}

class CliOutputWeb extends CliOutputDefault {	
	
	public function write($tag, $string, $args=array()){
        
        $values = isset($args["values"])?$args["values"]:array();
        $mode = isset($args["mode"])?$args["mode"]:array();
        
        switch($tag) {
									
			case "Header":
				?>
		        <html>
		        <head>
		            <title><?=$string?></title>
					<!-- Latest compiled and minified CSS -->
					<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

					<!-- Optional theme -->
					<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">
		        </head>
		        <body>
		        <div class="container">
		        <h1><?=$string?></h1>
		        <hr>
		        <?php
			break;

			case "Footer":
		   		?>
		        </div>
				<!-- Latest compiled and minified JavaScript -->
				<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
		        </body>
		        </html>
		        <?php
			break;

			case "Head":
            case "Title":
        		print "<h2>";
    			$this->out($string, $values);
        		print "</h2>";
			break;

			case "Paragraph":
            case "Comment":                
		        print '<div class="alert alert-info">';
		        $this->out($string, $values);
		        print '</div>';
			break;				

            case "Line":		        
				switch( strtolower(trim($mode))) {
				    case "error":
				        print "<p class='error'><i class='icon-circle-arrow-right'></i>&nbsp;";
				        break;
				    case "warn":
				        print "<p class='warning'><i class='icon-circle-arrow-right'></i>&nbsp;";
				        break;
				    case "info":
				        print "<p class='info'><i class='icon-circle-arrow-right'></i>&nbsp;";
				        break;
				    default:
				        print "<p ><i class='icon-circle-arrow-right'></i>&nbsp;";
				        break;
				}
				$this->out($string, $values);
				print "</p>";
			break;       

            case "Ok":
		        print "<p class='info'><i class='icon-circle-arrow-right'></i>&nbsp;";
		        $this->out($string, $values);
				print "</p>";
			break;				
        
            case "Error":
		        print "<p class='error'><i class='icon-circle-arrow-right'></i>&nbsp;";            
				$this->out($string, $values);
				print "</p>";				
			break;
        
            case "Object":        
                $this->outObject($string ,isset($args["hideEmpty"])?$args["hideEmpty"]:false );                        
            break;
        
            case "ObjectList":        		
                $this->outObjectList($string ,isset($args["hideEmpty"])?$args["hideEmpty"]:false );                                
            break;
        
			default:
				$this->out($string, $values);
		}
	}	

	public function outNl(){
		$this->out("<br/>");
	}

    public function outObject($obj ,$hideEmpty=false){
		$vars=false;
		if( is_object($obj) ) {
			$vars = get_object_vars($obj);			
		}		
		if( is_array($obj) ) {
			$vars = $obj;			
		}		
		if(!$vars){
			return;
		}
        
        $maxStr=0;
        foreach($vars as $i => $v){
            $maxStr = strlen($i) > $maxStr ? strlen($i) : $maxStr;
        }
        ?>
    	<form class="form-horizontal" role="form">
    	<?php
        foreach($vars as $i => $v){
            $s=$v;
            if($hideEmpty&&!$s) {
                continue;
            }

            if(is_object($v) || is_array($v)) {
                $s=print_r($v, true);
            }
            ?>
			<div class="form-group">
				<label class="col-sm-2 control-label">
				<?=str_pad($i, $maxStr, " ", STR_PAD_LEFT)?>
				</label>
				<div class="col-sm-10">
				<p class="form-control-static">
				<?=$s?>
				</p>
				</div>
			</div>
  			<?php            
        }
        ?>
       	</form>
       	<?php
    }

    public function outObjectList($objects, $hideEmpty=false ){
        $i=1;
        if(count($objects)) {
            foreach($objects as $o) {
                $this->out('<div class="alert alert-info"><strong>'.$i.'</strong></div>');
                $this->outObject($o, $hideEmpty);
                $i++;
            }
        } else {
            $this->out('<div class="alert alert-info"><strong>Empty list</strong></div>');
        }
        
    }        
}

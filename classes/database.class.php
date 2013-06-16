<?php 

namespace baobab;

class Database {

    public static $_instances = array(); 
    private $_conn;
    private $_dsn;
    private $_query;
    private $_queryParams;
    private $_errorInfo = false;
    private $_last_rs;

    protected function __construct( $host, $user, $pass, $db, $port = 3306, $driver_opt = array() ) {
        $this->connect($host, $user, $pass, $db, $port, $driver_opt);
    }

    protected function connect($host, $user, $pass, $db, $port = 3306, $driver_opt = array()) {
        $this->_dsn = "mysql:host=$host;dbname=$db;port=$port";        
        try {
            $this->_conn = new \PDO($this->_dsn, $user, $pass, $driver_opt);
        } catch (\PDOException $e) {
            throw new \Exception("Database connection error");
        }
        return true;
    }

    public static function getInstance( $options = array() ) {

        if( !$options ) {	//Retrieve default db info

			$cfg = Config::getInstance();

            $host 		= $cfg->get('db_host');
            $port		= $cfg->get('db_port');
            $user	 	= $cfg->get('db_user');
            $pass	 	= $cfg->get('db_pass');
            $driver_opt	= $cfg->get('db_options');

            //$db	= ( ! is_null($cfg->get('db_name')) ) ? $cfg->get('db_name') : 'information_schema';
            $db	= ( ! is_null($cfg->get('db_name')) ) ? $cfg->get('db_name') : 'bridge';

        } else {

            $host 		= isset( $options['host'] ) ? $options['host'] : 'localhost' ;
            $port 		= isset( $options['port'] ) ? $options['port'] : '3306' ;
            $user 		= isset( $options['user'] ) ? $options['user'] : 'root' ;
            $pass 		= isset( $options['pass'] ) ? $options['pass'] : '' ;
            $driver_opt	= isset( $options['options'] ) ? $options['options'] : array() ;

            //$db 	= isset( $options['db'] ) ? $options['db'] : 'information_schema' ;
            $db 	= isset( $options['db'] ) ? $options['db'] : 'bridge' ;
        }

        $dsn = "mysql:host=$host;dbname=$db;port=$port";        
        if (!isset(self::$_instances[$dsn])) {
            self::$_instances[$dsn] = new self($host, $user, $pass, $db, $port, $driver_opt);
        }
        return self::$_instances[$dsn];
    }

    public function getConnectionInfo() {
        return $this->_dsn;
    }

    public function setQuery($query, $params=array()) {
        $this->_query = $query;
        $this->_queryParams = $params;
        $this->_last_rs = false;
    }

    public function query() {
        $this->_errorInfo = null;
        $this->_last_rs = false;

        $rs = $this->_conn->prepare( $this->_query );
        $this->_last_rs = $rs;

        if ( ! $rs ) {
            $this->setErrorInfo( $rs->errorInfo() );
            return false;
        }

        if(! $rs->execute( $this->_queryParams ) ) {
            $this->setErrorInfo( $rs->errorInfo() );
            return false;
        }

        return true;
    }

    public function insertId($name = null) {
        return $this->_conn->lastInsertId($name);
    }

    public function loadObjectList($key = '', $class = 'stdClass') {

        if(!$this->_last_rs) {
            $this->query();
        }

        $array = array();
        // Get all of the rows from the result set as objects of type $class.
        while ($row = $this->fetchObject($this->_last_rs, $class))
        {
            if ($key)
            {
                $array[$row->$key] = $row;
            }
            else
            {
                $array[] = $row;
            }
        }
        return $array;
    }

    public function loadObject($class = 'stdClass') {

        if(!$this->_last_rs) {
            $this->query();
        }
        return $this->fetchObject($this->_last_rs, $class);
    }

    public function loadRow() {

        if(!$this->_last_rs) {
            $this->query();
        }
        return (array) $this->fetchObject($this->_last_rs);
    }

    public function loadRowList($key = null) {

        if(!$this->_last_rs) {
            $this->query();
        }
        $class = 'stdClass';
        $array = array();
        // Get all of the rows from the result set as objects of type $class.
        while ($row = $this->fetchObject( $this->_last_rs, $class))
        {
            if ($key)
            {
                $array[$row->$key] = (array) $row;
            }
            else
            {
                $array[] = (array) $row;
            }
        }
        return $array;
    }

    ///
    //  Private
    ///

    protected function fetchObject($cursor = null, $class = 'stdClass') {

        if ( !empty($cursor) && $cursor instanceof \PDOStatement ) {            
			return $cursor->fetchObject($class);
        }
        return false;
        //if ($this->prepared instanceof PDOStatement) {
        //    return $this->prepared->fetchObject($class);
        //}
    }


	protected function setErrorInfo($error_info) {
		$this->_errorInfo = $error_info;
        Log::error("_DATABASE_:".$error_info);
	}

	public function getErrorInfo() {
		return $this->_errorInfo;
	}
}

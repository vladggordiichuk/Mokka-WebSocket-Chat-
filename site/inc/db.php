<?php 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
//header("Content-Type: application/json; charset=UTF-8");

function sendAnswer($aAnsw){
    $jBuf = json_encode( $aAnsw );
    ob_end_clean();
    exit($jBuf);
}

class DB {
    private $_con = null;

    public function __construct($host,$username,$password,$dbname,$port=null) {       
        $this->_con = @mysqli_connect($host,$username,$password,$dbname,$port);
        $this->_checkError();        
        $this->_con->set_charset('utf8');        
    }
    
    private function _checkError(){
        if(mysqli_connect_errno()){
            exit( json_encode( array( 'error'=>mysqli_connect_error() ) ) );         
        }
    }   

    public function escape($data){
        return mysqli_real_escape_string($this->_con, $data);
    }
    public function query($sql) {        
        $result = @mysqli_query($this->_con, $sql);
        $this->_checkError();
        if($result) {         
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        }        
        return null;
    }
    public function exec($sql) {        
        $result = @mysqli_query($this->_con, $sql);
        $this->_checkError();       
        return $result;
    }
}
$db = new DB(DBHOST,DBUSER,DBPASW,DBNAME,DBPORT);

ob_start();
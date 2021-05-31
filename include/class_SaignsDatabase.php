<?php

class SaignsDatabase {
	
	var $sUrl;
	var $sUsername;
	var $sPassword;
	
	var $sLog;
	var $bEchoException;
	var $bTableQuote;
	var $bIsFirebird;
	var $iCounter;

	var $pdo;
	
	public function __construct($url,$username,$password)
	{
	    $this->sUrl = $url;
		$this->sUsername = $username;
		$this->sPassword = $password;
		$this->bTableQuote = TRUE;
		$this->bIsFirebird = FALSE;
		$this->iCounter = 0;
		if ( strpos($url,"firebird:") !== FALSE )
		{
			$this->bTableQuote = FALSE;
			$this->bIsFirebird = TRUE;
		}
		$this->connect();
	}	
	public function log_excpeption($message,$e)
	{
	    if ( $this->sLog == "" )
	    {
	        return;
	    }
	    
	    if ( !is_dir($this->sLog) )
	    {
	        mkdir($this->sLog, 0777, TRUE);
	    }
	    
	    $buffer = $_SERVER["REMOTE_ADDR"]."\n\n";
	    $buffer .= $message."\n\n";
	    if ( isset($this->pdo) && isset($this->pdo->errorInfo()[2]) )
	    {
	        $buffer .= $this->pdo->errorInfo()[2]."\n\n";
	    }
	    $buffer .= print_r($e,true)."\n\n";
	    if ( function_exists("callstack") ) $buffer .= callstack("\n")."\n\n";
	    file_put_contents($this->sLog."/database_error_".ms().".txt",$buffer);
	    if ( $this->bEchoException )
	    {
	        echo("<pre>".$buffer."</pre>");
	    }
	}
	
	public function connect()
	{
	    $this->pdo = NULL;
	    try
	    {
	        $this->pdo = new PDO($this->sUrl,$this->sUsername,$this->sPassword);
	        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	    }
	    catch (Exception $e)
	    {
	        $this->log_excpeption("connection failed",$e);
	        exit();
	    }
	}
	
	public function affected_rows()
	{
	    return $this->pdo->rowCount();
	}
	
	public function insert_id()
	{
	    return $this->pdo->lastInsertId();
	}
	
	public function quote($text)
	{
	    return $this->pdo->quote($text);
	}
	
	/**
	 * Query
	 * 
	 * @param unknown $message
	 * @return PDOStatement
	 */
	public function q($message)
	{
	    try
	    {
	        if ( class_exists("SaignsTiming") ) SaignsTiming::start($message);
	        $query = $this->pdo->query($message,PDO::FETCH_ASSOC);
	        if ( class_exists("SaignsTiming") ) SaignsTiming::end($message);
	        if ( !$query )
	        {
	            $this->log_excpeption($message,"");
	            exit();
	        }
	        $this->iCounter++;
	        return $query;
	    }
	    catch (Exception $e)
	    {
	        $this->log_excpeption($message,$e);
	        exit();
	    }
	}
	
	/**
	 * Execute
	 * 
	 * @param unknown $message
	 * @return number
	 */
	public function e($message)
	{
	    try
	    {
	        if ( class_exists("SaignsTiming") ) SaignsTiming::start($message);
	        $count = $this->pdo->exec($message);
	        if ( class_exists("SaignsTiming") ) SaignsTiming::end($message);
	        $this->iCounter++;
	        return $count;
	    }
	    catch (Exception $e)
	    {
	        $this->log_excpeption($message,$e);
	        exit();
	    }
	}
	
	/**
	 * Fetch one row as array
	 * 
	 * @param unknown $message
	 * @return NULL|mixed
	 */
	public function a($message)
	{
		$query = $this->q($message);
		$row = $query->fetch(PDO::FETCH_ASSOC);
		if ( !$row )
		{
			return NULL;
		}
		return $row;
	}
	
	/**
	 * Fetch one value of one row
	 * 
	 * @param unknown $message
	 * @return NULL|mixed
	 */
	public function f($message)
	{
		$query = $this->q($message);
		if ( !$query )
		{
		    return "";
		}
		$row = $query->fetchColumn();
		if ( !$row )
		{
		    return "";
		}
		return $row;
	}
	
	/**
	 * Fetch an array of multiple rows 
	 */
	public function an($message)
	{
	    $data = array();
	    foreach ( $this->q($message) as $row )
	    {
	        $data[] = $row;
	    }
	    return $data;
	}
	
	/**
	 * Fetch an assoc array of multiple rows by given index of row
	 * 
	 * @param unknown $message
	 * @param unknown $index
	 * @return PDOStatement[]
	 */
	public function ans($message,$index)
	{
		$data = array();
		foreach ( $this->q($message) as $row )
		{
			$data[$row[$index]] = $row;
		}
		return $data;
	}

	/**
	 * Prepare Statement
	 * 
	 * @param unknown $query
	 * @param array $data
	 * @return PDOStatement
	 */
	public function prepare($query,$data=array())
	{
	    if ( count($data) == 0 )
	    {
	        return $this->pdo->prepare($query);
	    }
	    
	    $stmt = $this->pdo->prepare($query);
	    $column = 1;
	    foreach( $data as $value )
	    {
	        $stmt->bindValue($column++, $value);
	    }
	    return $stmt;
	}
	
	/**
	 * Execute Statement
	 * @param PDOStatement $stmt
	 */
	public function es(PDOStatement $stmt)
	{
	    try {
	        if ( class_exists("SaignsTiming") ) SaignsTiming::start($stmt->queryString);
	        $stmt->execute();
	        if ( class_exists("SaignsTiming") ) SaignsTiming::end($stmt->queryString);
	        $this->iCounter++;
	    }
	    catch(Exception $e)
	    {
	        $this->log_excpeption("stmt::execute", $e);
	        exit();
	    }
	}
	
	/**
	 * Insert
	 * 
	 * @param unknown $table
	 * @param unknown $data
	 * @return void|string
	 */
	public function i($table,$data)
	{
	    $buffer1 = "";
	    $buffer2 = "";
	    $z = 0;
	    foreach( $data as $param => $value )
	    {
	        if ( $this->bTableQuote )
	        {
	            $buffer1 .= "`".$param."`";
	        }
	        else
	        {
	            $buffer1 .= "".$param."";
	        }
	        $buffer2 .= "?";
	        $z++;
	        if ( $z < count($data) ) {
	            $buffer1 .= ',';
	            $buffer2 .= ',';
	        }
	    }
	    
	    $query = "insert into ".$table." (".$buffer1.") values (".$buffer2.")";
	    
	    $stmt = $this->pdo->prepare($query);
	    $column = 1;
	    foreach( $data as $param => $value )
	    {
	        $stmt->bindValue($column++, $value);
	    }
	    
	    try
	    {
	        if ( class_exists("SaignsTiming") ) SaignsTiming::start($query);
	        $stmt->execute();
	        if ( class_exists("SaignsTiming") ) SaignsTiming::end($query);
	        $this->iCounter++;
	    }
	    catch (Exception $e)
	    {
	        $this->log_excpeption($message,$e);
	        exit();
	    }
	    
	    if ( $this->bIsFirebird )
	    {
	        return;
	    }
	    
	    return $this->pdo->lastInsertId();
	}
	
	/**
	 * Classic insert
	 * 
	 * @param unknown $query
	 * @param unknown $data
	 * @return void|string
	 */
	public function insert($query,$data)
	{
	    $buffer1 = "";
	    $buffer2 = "";
	    $z = 0;
	    foreach( $data as $param => $value )
	    {
	        if ( $this->bTableQuote )
	        {
	            $buffer1 .= "`".$param."`";
	        }
	        else
	        {
	            $buffer1 .= "".$param."";
	        }
	        $buffer2 .= "?";
	        $z++;
	        if ( $z < count($data) )
	        {
	            $buffer1 .= ',';
	            $buffer2 .= ',';
	        }
	    }
	    
	    $query = str_replace("%0%",$buffer1,$query);
	    $query = str_replace("%1%",$buffer2,$query);
	    
	    $stmt = $this->pdo->prepare($query);
	    $column = 1;
	    foreach( $data as $param => $value )
	    {
	        $stmt->bindValue($column++, $value);
	    }
	    
	    try
	    {
	        if ( class_exists("SaignsTiming") ) SaignsTiming::start($query);
	        $stmt->execute();
	        if ( class_exists("SaignsTiming") ) SaignsTiming::end($query);
	        $this->iCounter++;
	    }
	    catch (Exception $e)
	    {
	        $this->log_excpeption($message,$e);
	        exit();
	    }
	    
	    if ( $this->bIsFirebird )
	    {
	        return;
	    }
	    
	    return $this->pdo->lastInsertId();
	}
	
	/**
	 * Classic update 
	 * 
	 * @param unknown $query
	 * @param unknown $data
	 * @return number
	 */
	public function update($query,$data)
	{
		$buffer = "";
		$z = 0;
		foreach( $data as $param => $value )
		{
			if ( $this->bTableQuote )
			{
				$buffer .= "`$param` = ?";
			}
			else
			{
				$buffer .= "$param = ?";
			}
			$z++;
			if ( $z < count($data) )
			{
				$buffer .= ',';
			}
		}
		$query = str_replace("%0%",$buffer,$query);
		
		$stmt = $this->pdo->prepare($query);
		$column = 1;
		foreach( $data as $param => $value )
		{
			$stmt->bindValue($column++, $value);
		}
			
		try
		{
		    if ( class_exists("SaignsTiming") ) SaignsTiming::start($query);
		    $stmt->execute();
		    if ( class_exists("SaignsTiming") ) SaignsTiming::end($query);
		    $this->iCounter++;
		    return $stmt->rowCount();
		}
		catch (Exception $e)
		{
		    $this->log_excpeption($message,$e);
		    exit();
		}
	}
	
}

?>
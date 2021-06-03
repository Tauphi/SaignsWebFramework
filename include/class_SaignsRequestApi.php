<?php

class SaignsRequestApi
{
	var $content;
	var $redirect;
	var $focus;
	var $reload;

	public function __construct()
	{
		$this->content = array();
		$this->redirect = "";
		$this->reload = "";
		$this->focus = g("requestapi_focus");
	}



	public function __toString()
	{
		return $this->json();
	}
	
	
	public function exit($error="")
	{
		if ( $error != "" )
		{
			$this->error($error);
		}
		echo($this);
		exit();
	}



	public static function is()
	{
		return g("requestapi_set") == "1";
	}
	
	
	
	public static function is_execute($execute)
	{
		return g("execute") == $execute;
	}



	public function show( $id )
	{
		$this->add($id, "show", 0);
	}



	public function hide( $id )
	{
		$this->add($id, "hide", 0);
	}



	public function showhtml( $id , $html )
	{
		$this->add($id, "show", 1, $html);
	}



	public function html( $id , $html )
	{
		$this->add($id, "html", 1, $html);
	}
	
	
	
	
	public function val( $id , $html )
	{
		$this->add($id, "val", 1, $html);
	}



	public function error( $message )
	{
		$this->showhtml("#error_content", '<i class="fas fa-exclamation-circle"></i> '.$message);
		$this->show("#error_section");
	}
	
	
	
	public function success( $message )
	{
		$this->showhtml("#success_content", '<i class="fas fa-check-circle"></i> '.$message);
		$this->show("#success_section");
	}



	public function redirect( $url )
	{
		$this->redirect = $url;
	}
	
	
	
	
	public function reload( )
	{
		$this->reload = '1';
	}
	
	
	
	public function call( $call , ...$args )
	{
		$this->content[] = array(
			"id" => "",
			"type" => "call",
			"call" => $call,
			"callargs" => $args,
			"sethtml" => 0,
			"html" => "");
	}
	



	/**
	 * 
	 * @param unknown $id cssSelector
	 * @param unknown $type show|hide|html|val|effect
	 * @param unknown $sethtml 1|0
	 * @param unknown $html htmlContent
	 */
	public function add( $id = '' , $type = '' , $sethtml = 0 , $html = '' )
	{
		$this->content[] = array(
			"id" => $id,
			"type" => $type,
			"call" => "",
			"callargs" => array(),
			"sethtml" => $sethtml,
			"html" => $html);
	}



	public function json()
	{
		global $webcontroller;
		
		$result = array(
			"content" => $this->content,
			"redirect" => $this->redirect,
			"reload" => $this->reload,
			"focus" => $this->focus,
			"fsv" => $webcontroller->fsv_create(),
		);
		return json_encode($result, JSON_PRETTY_PRINT);
	}

}

?>
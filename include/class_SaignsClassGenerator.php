<?php

class SaignsClassGenerator
{

public static function generate( SaignsDatabase $db , $shema , $tablename , $class , $primary = "id" )
{
	$columns = array();
	foreach ( $db->an("SELECT `column_name` FROM `INFORMATION_SCHEMA`.`COLUMNS`  WHERE `TABLE_SCHEMA`='".$shema."'  AND `TABLE_NAME`='".$tablename."'") as $row )
	{
		$columns[] = $row['column_name'];
	}
	
	ob_start();
?>class {$class} {
	{foreach $columns as $col}var ${$col};
	{/foreach}
	
	var $valid;
	
	public function __construct(${$primary}='')
	{
		global $mysql;
		
		$this->valid = 0;
		if ( ${$primary} != '' )
		{
			$data = $mysql->a("select {for $i = 0 ; $i < count($columns) ; $i++}{$columns[$i]}{if $i+1 < count($columns)}, {/if}{/for} from {$table} where {$primary} = '".${$primary}."'");
			if ( $data )
			{
				$this->set_by_result($data);
			}
		}
	}
	
	public function set_by_request()
	{
		foreach ( $_REQUEST as $param => $value )
		{
			if ( property_exists('{$class}',$param) ) $this->{$gka}$param} = $value;
		}
	}
	
	public function set_by_result($data)
	{
		foreach ( $data as $param => $value )
		{
			if ( property_exists('{$class}',$param) ) $this->{$gka}$param} = $value;
		}
		
		//All special below
		{foreach $columns as $col}{if strstr($col,"date")}$this->{$col} = strtotime($data['{$col}']);
		{/if}{/foreach}
		$this->valid = 1;
	}
	
	public function save()
	{
		global $mysql;
		
		// date("Y-m-d H:i:s",$this->startdate);
		
		$update = array();
		{foreach $columns as $col}{continue $col == $primary}$update['{$col}'] = {if strstr($col,"date")}date("Y-m-d H:i:s",$this->{$col}){else}$this->{$col}{/if};
		{/foreach}
		//$update['lastupdate'] = date("Y-m-d H:i:s");

		if ( $this->exists() )
		{
			$mysql->update("update {$table} set %0% where {$primary} = '".$this->{$primary}."'", $update);
		}
		else
		{
			//$this->createdate = time();
			//$update['createdate'] = date("Y-m-d H:i:s",$this->createdate);
		
			$this->{$primary} = $mysql->insert("insert into {$table} (%0%) values (%1%)",$update);
			$this->valid = 1;
		}
	}
	
	public static function get_all()
	{
		global $mysql;

		$temp = array();
		foreach ( $mysql->an("select * from {$table} order by xxxx") as $data )
		{
			$obj = new {$class}();
			$obj->set_by_result($data);
			$temp[] = $obj;
		}
		return $temp;
	}
	
	public function delete()
	{
		global $mysql;

		$mysql->q("delete from {$table} where {$primary} = '".$this->{$primary}."'");
		$this->valid = 0;
		$this->{$primary} = "";
	}
	
	public function exists()
	{
		return $this->valid == 1;
	}
	

}<?
		$buffer = ob_get_contents();
		ob_clean();

		$temp = fc($buffer, array(
			'class' => $class,
			'columns' => $columns,
			'primary' => $primary,
			'gka' => '{',
			'shema' => $shema,
			'tablename' => $tablename,
			'table' => $shema.'.'.$tablename));
		echo ("<pre>&lt;?\r\n\r\n");
		echo $temp;
		echo ("\r\n\r\n?></pre>");
	}
}
	
?>
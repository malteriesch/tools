<?php
namespace UnitTest\Lib\TestDbAcle; 
abstract class UpsertBuilder {
	
	var $sTablename;
	var $aColumns = array();
	
	public function __construct( $sTablename ) {
		
		$this->sTableName = $sTablename;
	}
	
	public function AddColumn( $sName, $sValue, $isExpression=false ) {
		
		$this->aColumns[ $sName ] = array("value"=>$sValue,"isExpression"=>$isExpression);
	}
	
	protected function _GetCopyOfColumnsForManipulation() {
		return $this->aColumns;
	}
	
	abstract public function GetSql();
	
}
?>
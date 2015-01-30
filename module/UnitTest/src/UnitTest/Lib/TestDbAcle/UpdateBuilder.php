<?php
namespace UnitTest\Lib\TestDbAcle; 

class UpdateBuilder extends UpsertBuilder {
	
	private $sId;
	private $sPrimaryKeyColumn;
	
	public function __construct( $sTablename, $sId, $sPrimaryKeyColumn = 'id' ) {
		
		parent::__construct($sTablename);
		
		$this->sId 				 = $sId;
		$this->sPrimaryKeyColumn = $sPrimaryKeyColumn;
		
	}
	
	public function GetSql() {
		$aColumns =$this->_GetCopyOfColumnsForManipulation();
		array_walk( $aColumns, create_function( '&$sValue, $sKey', '$sValue = "$sKey = \'$sValue\'";') );
		$sAssignments  = implode( ', ', $aColumns );
		
		return "UPDATE {$this->sTableName} SET {$sAssignments} WHERE {$this->sPrimaryKeyColumn} = '{$this->sId}'";
		
	}
	
}
?>
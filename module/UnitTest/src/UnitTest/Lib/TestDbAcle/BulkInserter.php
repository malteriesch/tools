<?php

namespace UnitTest\Lib\TestDbAcle;

class BulkInserter extends UpsertBuilder {
    var $rows=array();
    protected function escapeValues(&$value){
        $actualValue = addslashes($value['value']);
        if ($value['isExpression']) {
            $value = $actualValue;
        }else{
            $value = "'".$actualValue."'";
        }
    }
    
    public function addRow(BulkInserterRow $row){
        $this->rows[] = $row;
    }

    public function GetSql() {

        

        $aColumnNames = array_keys($this->rows[0]->GetCopyOfColumnsForManipulation());
        array_walk($aColumnNames,function(&$column){
            $column = "`$column`";
        });
        
        $sColumnNames = implode(', ', $aColumnNames);
        
        $sql = "INSERT INTO {$this->sTableName} ( {$sColumnNames} ) VALUES ";
        $valueStrings = array();
        foreach ($this->rows as $row){
            $aColumns     = $row->GetCopyOfColumnsForManipulation();
            
            array_walk($aColumns, array($this,'escapeValues'));
            $sValues = implode(', ', $aColumns);
            $valueStrings[]="($sValues)";
            
        }
        $sql.=implode(",",$valueStrings);
        return $sql;
    }

}


class BulkInserterRow {
	var $aColumns = array();
	
	
	public function AddColumn( $sName, $sValue, $isExpression=false ) {
		
		$this->aColumns[ $sName ] = array("value"=>$sValue,"isExpression"=>$isExpression);
	}
	
	public function GetCopyOfColumnsForManipulation() {
		return $this->aColumns;
	}
    
}
?>
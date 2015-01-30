<?php

namespace UnitTest\Lib\TestDbAcle;

class InsertBuilder extends UpsertBuilder {
    protected function escapeValues(&$value){
        $actualValue = addslashes($value['value']);
        if ($value['isExpression']) {
            $value = $actualValue;
        }else{
            $value = "'".$actualValue."'";
        }
    }

    public function GetSql() {

        $sColumnNames = implode(', ', array_keys($this->aColumns));
        $aColumns     = $this->_GetCopyOfColumnsForManipulation();

        array_walk($aColumns, array($this,'escapeValues'));

        $sValues = implode(', ', $aColumns);

        return "INSERT INTO {$this->sTableName} ( {$sColumnNames} )
		        VALUES ( {$sValues} )";
    }

}

?>
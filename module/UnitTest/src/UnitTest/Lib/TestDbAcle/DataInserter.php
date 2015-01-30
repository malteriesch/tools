<?php

namespace UnitTest\Lib\TestDbAcle;

class DataInserter {

    private $aNoNullVolumns   = array();
    private $aNullableColumns = array();
    private $aPrimaryKeys = array();

    /**
     * @var \PDO
     */
    private $oPdo;

    public function __construct(\PDO $pdo = null) {
        $this->oPdo = $pdo;
    }

    public function ExecuteMultipleCsv($aDataTree) {
        foreach ($aDataTree as $sTable => $content) {
            $this->ExecuteCsv($sTable, $content);
        }
    }

    private function UpdateTableInfo($sTable) {


        if (!isset($this->aNoNullVolumns[$sTable])) {

            $this->aNoNullVolumns[$sTable] = $this->oPdo->query('describe ' . $sTable)->fetchAll();
        }
        foreach ($this->aNoNullVolumns[$sTable] as $aColInfo) {
            if ($aColInfo['Null'] == 'YES') {
                $this->aNullableColumns[$sTable][] = $aColInfo['Field'];
            }
            if ($aColInfo['Key'] == 'PRI') {
                $this->aPrimaryKeys[$sTable]=$aColInfo['Field'];
            }
        }
    }

    private function _GenerateDefaultNullValue($sColumnType) {
        if (strpos($sColumnType, 'int') !== false) {
            return '1';
        }

        if (strpos($sColumnType, 'varchar') !== false) {
            return 'D';
        }
        if (strpos($sColumnType, 'text') !== false) {
            return 'T';
        }
        if (strpos($sColumnType, 'date') !== false) {
            return '2000-01-01';
        }
    }

    private function _AddDefaultNullValues($sTable, $aColumns) {

        foreach ($this->aNoNullVolumns[$sTable] as $aColInfo) {
            $sColumnName = $aColInfo['Field'];
            if (!isset($aColumns[$sColumnName]) && $aColInfo['Null'] == 'NO' && $aColInfo['Extra'] != "auto_increment" && !is_null($aColInfo['Default'])) {
                $aColumns[$sColumnName] = $this->_GenerateDefaultNullValue($aColInfo['Type']);
            }
        }
        return $aColumns;
    }

    public function ExecuteCsv($sTable, $content, $bTruncate = false) {
        $this->ClearTable($sTable);
        

        $this->UpdateTableInfo($sTable);
        if (count($content)==0) {
            return;
        }
       

        $aValuesToBeInserted = array();
        $bIsUpdate = false;
        $bulkInserter = new BulkInserter($sTable);

        foreach ($content as $aValuesToBeInserted) {
            $insertRow = new BulkInserterRow();
            $aToUpsert = array();
            
            //$oUpserter           = new InsertBuilder($sTable);
            $aValuesToBeInserted = $this->_AddDefaultNullValues($sTable, $aValuesToBeInserted);

            foreach ($aValuesToBeInserted as $sSqlCol => $sSqlValue) {
                if ($sSqlValue == '' && in_array($sSqlCol, (array) $this->aNullableColumns[$sTable])) {
                    $insertRow->AddColumn($sSqlCol, 'NULL', true);
                } else {
                    $insertRow->AddColumn($sSqlCol, $sSqlValue);
                }
            }
            $bulkInserter->addRow($insertRow);
        }
        $sSql = $bulkInserter->GetSql();
        $this->oPdo->exec($sSql);
        
    }

    public function ClearTable($sTable) {
        $this->oPdo->exec("truncate table {$sTable}");
    }

}


?>
<?php


namespace UnitTest\Lib\TestDbAcle;
class CsvFormatter {
	
	static function format( $aToFormat,$aExclude=array() ) {
		
		foreach($aToFormat as $iIdx=>$aRow) {
			foreach ($aRow as $sKey=>$sValue) {
                            if (is_null($sValue )){
                                $aToFormat[$iIdx][$sKey]="NULL";
                            }
                            $aToFormat[$iIdx][$sKey] = str_replace("\n","",$aToFormat[$iIdx][$sKey]);
                            $aToFormat[$iIdx][$sKey] = str_replace("\r","",$aToFormat[$iIdx][$sKey]);
                            $aToFormat[$iIdx][$sKey] = htmlentities($aToFormat[$iIdx][$sKey]);
                            if (in_array($sKey,$aExclude)) {
                                    unset($aToFormat[$iIdx][$sKey]);
                            }
			}
		}
		
		
		$aRowKeys = array_keys($aToFormat);
		$aFields = array_keys($aToFormat[$aRowKeys[0]]);
		$aMaxLengths=array();
		
		foreach ($aFields as $iKey=>$sField) {
			if (!isset($aMaxLengths[$sField]) || $aMaxLengths[$sField]<strlen($sField))
					$aMaxLengths[$sField]=strlen($sField);
		}
		
		foreach ($aToFormat as $aRow) {
			foreach ($aRow as $sKey=>$sCol) {
				if (!isset($aMaxLengths[$sKey]) || $aMaxLengths[$sKey]<strlen($sCol))
					$aMaxLengths[$sKey]=strlen($sCol);
			}
		}

		foreach ($aFields as $iKey=>$sField) {
			$aFields[$iKey] = str_pad($sField,$aMaxLengths[$sField]+3);
		}
		
		$sOut= implode("|",$aFields);
		
		foreach ($aToFormat as $aRow) {
			foreach ($aRow as $sKey=>$sCol) {
				$aRow[$sKey] = str_pad($sCol,$aMaxLengths[$sKey]+3);
			}
			$sOut.="\n".implode("|",$aRow);
		}
		return $sOut;
	}
	
}

?>
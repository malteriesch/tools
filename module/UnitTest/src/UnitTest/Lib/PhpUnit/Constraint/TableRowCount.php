<?php
namespace UnitTest\Lib\PhpUnit\Constraint;
use SebastianBergmann\Exporter\Exporter;

class TableRowCount extends \PHPUnit_Extensions_Database_Constraint_TableRowCount{
    public function __construct($tableName, $value)
    {
        parent::__construct($tableName, $value);
        $this->exporter = new Exporter();
    }
}
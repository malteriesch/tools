<?php
namespace UnitTest\Lib;

class DatabaseConnector 
{
    static $connection;
    
    public static function getConnection($config)
    {
        if (!isset(self::$connection)){
            self::$connection = new \Zend\Db\Adapter\Adapter($config);
            self::$connection->getDriver()->getConnection()->connect();
        }
        return self::$connection;
    }
}
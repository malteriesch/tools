<?php
namespace Application\Service;

class DatabaseFactory 
{
    
    protected $serviceLocator;
    
    function __construct($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    function getDatabases()
    {
        $databases = array_keys( $this->serviceLocator->get('Config')['databases']);
        return array_combine($databases,$databases);
    }
    
    function createService($databaseName){
        return new \Zend\Db\Adapter\Adapter($this->serviceLocator->get('Config')['databases'][$databaseName]);
    }
}
<?php

namespace UnitTest\Lib\TestCase;

use UnitTest\Lib\BaseTestCase;

class AbstractModuleTestCase extends BaseTestCase {


    /* @var $serviceManager \Zend\ServiceManager\ServiceManager */
    protected $serviceManager;
    protected $moduleManager;

    function setUp(){
        parent::setUp();
        $this->serviceManager = new \Zend\ServiceManager\ServiceManager(new \Zend\Mvc\Service\ServiceManagerConfig() );
        $this->serviceManager->setService('ApplicationConfig', include 'config/application.config.php');
        $this->moduleManager = $this->serviceManager->get('ModuleManager');
        $this->setModules();
        $this->moduleManager->loadModules();
        $this->serviceManager->setAllowOverride(true);
    }

    protected function setModules()
    {

    }



    protected function assertClass($expectedClass, $testedObject ){
        $this->assertEquals($expectedClass, get_class($testedObject));
    }


}
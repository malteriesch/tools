<?php
namespace UnitTest\Lib\TestCase;

use UnitTest\Lib\BaseTestCase;

abstract class ModuleTestCase extends AbstractModuleTestCase {

    protected function setModules()
    {
        $this->moduleManager->setModules(array($this->getModuleNamespace()));
    }

    protected function getModuleFolder(){
        $module = $this->getModule();
        $reflector = new \ReflectionClass($module);
        $filename = $reflector->getFileName();
        return dirname($filename);
    }

    function test_getAutoloaderConfig(){
        $dir = $this->getModuleFolder();
        $nameSpace = $this->getModuleNamespace();
        $module = $this->getModule();
        $config = $module->getAutoloaderConfig();
        $this->assertEquals(array(
                'Zend\Loader\ClassMapAutoloader' => array(
                        $dir . '/autoload_classmap.php',
                ),
                'Zend\Loader\StandardAutoloader' => array(
                        'namespaces' => array(
                                $nameSpace => $dir . '/src/' . $nameSpace,
                        ),
                ),
        ),$config);

    }

    public function test_isEnabledInApplicationConfig()
    {
        $applicationConfig = include('config/application.config.php');
        $this->assertTrue(in_array($this->getModuleNamespace(),$applicationConfig['modules']),'Module '.$this->getModuleNamespace().' is enabled in application.config.php');
    }

    public function test_getConfig()
    {
        $this->assertEquals(include $this->getModuleFolder() . '/config/module.config.php',  $this->getModule()->getConfig());
    }

    protected function assertSameLocator($locatorName, $testedObject){
        $this->assertSame($this->serviceManager->get($locatorName), $testedObject);
    }

    protected function getFromControllerPluginManager($name)
    {
        return $this->serviceManager->get('ControllerPluginManager')->get($name);
    }

    protected function getFromViewHelperManager($name)
    {
        return $this->serviceManager->get('ViewHelperManager')->get($name);
    }

    protected function getFromControllerLoader($name)
    {
        return $this->serviceManager->get('ControllerLoader')->get($name);
    }

    protected function setDependency($classNameOrMock,$serviceKeyName = false, $serviceManagerKey = false)
    {
        if (!$serviceKeyName) {
            $serviceKeyName = $classNameOrMock;
        }
        if (is_string($classNameOrMock)) {
            $mock = \Mockery::mock($classNameOrMock);
        }else{
            $mock = $classNameOrMock;
        }
        if (!$serviceManagerKey) {
            $this->serviceManager->setService($serviceKeyName, $mock);
        } else {
            $this->serviceManager->get($serviceManagerKey)->setService($serviceKeyName, $mock);
        }
        return $mock;
    }

    protected function createMvcEventWithViewModel($viewModel){
        $mockApplication = \Mockery::mock('\Zend\Mvc\Application');
        $mockApplication->shouldReceive('getServiceManager')->andReturn($this->serviceManager);

        $event = new \Zend\Mvc\MvcEvent();
        $event->setViewModel($viewModel);
        $event->setApplication($mockApplication);
        return $event;
    }

    protected function getEventWithEventManager()
    {
        $mockEventManager = \Mockery::mock('\Zend\EventManager');

        $mockApplication = \Mockery::mock('\Zend\Mvc\Application');
        $mockApplication->shouldReceive('getEventManager')->andReturn($mockEventManager);

        $event = new \Zend\Mvc\MvcEvent();
        $event->setApplication($mockApplication);
        return [$mockEventManager,$event];
    }
            
    protected function assertBootstrapRenderEvent($method)
    {
        
        list($mockEventManager,$event) = $this->getEventWithEventManager();

        $module = $this->getModule();

        $mockEventManager->shouldReceive('attach')->once()->with(\Zend\Mvc\MvcEvent::EVENT_RENDER,array($module,$method));

        $this->assertTrue(is_callable(array($module,$method)));
        $module->onBootstrap($event);
    }
    


    abstract protected function getModule();
    abstract protected function getModuleNamespace();

}
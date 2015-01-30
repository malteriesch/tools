<?php
namespace UnitTest;

class Module
{

    public function getAutoloaderConfig()
    {

        return array(
                'Zend\Loader\ClassMapAutoloader' => array(
                        __DIR__ . '/autoload_classmap.php',
                ),
                'Zend\Loader\StandardAutoloader' => array(
                        'namespaces' => array(
                                __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                        ),
                ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getControllerConfig()
    {
        return array(
                'initializers' => array(
                       function($instance, \Zend\Mvc\Controller\ControllerManager $serviceManager) {
//                               if (strpos(get_class($instance),'UnitTest')!==false && APPLICATION_ENV!="test") {
//                                    throw new \Exception("Not in test environment");
//                               }
                        }
                ),
                'factories' => array(
                        'UnitTest\Controller\StoryHelper' => function($controllerManager){
                            $serviceLocator = $controllerManager->getServiceLocator();
                            $controller = new \UnitTest\Controller\StoryHelperController();
                            $controller->setSessionStorage( $serviceLocator->get('SessionStorage') );
                            return $controller;
                         },
                ),
        );
    }
}
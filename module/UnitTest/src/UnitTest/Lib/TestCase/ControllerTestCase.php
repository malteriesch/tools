<?php
namespace UnitTest\Lib\TestCase;

use Mockery\Matcher\Any;

use ApplicationTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Application\Controller\IndexController;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\ServiceManager\ServiceManager;
use Zend\Mvc\Service\ServiceManagerConfig;


abstract class ControllerTestCase extends \UnitTest\Lib\BaseTestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;
    protected $mockFormPlugin;
    protected $mockGridControllerPlugin;
    protected $mockForwardPlugin;
    protected $mockParamsPlugin;
    protected $mockFilePlugin;

    const FORWARDED = 'forwarded';

    protected function onCreate()
    {
        \Mockery::getConfiguration()->allowMockingNonExistentMethods(true);
    }
    
    protected function setUpController(\Zend\Mvc\Controller\AbstractActionController $controller)
    {

        $config = include 'config/application.config.php';

        $serviceManager = new ServiceManager(new ServiceManagerConfig());
        $serviceManager->setService('ApplicationConfig', $config);
        $serviceManager->get('ModuleManager')->loadModules();
        $serviceManager->setAllowOverride(true);

        $this->controller = $controller;
        $this->request    = new Request();
        $this->response   = new Response();
        $this->routeMatch = new RouteMatch(array('controller' => 'index'));
        $this->event      = new MvcEvent();

        $this->event->setRequest( $this->request)
        		    ->setResponse($this->response);

        $config = $serviceManager->get('Config');
        $routerConfig = isset($config['router']) ? $config['router'] : array();
        $router = HttpRouter::factory($routerConfig);
        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($serviceManager);
        $this->setupForwardPlugin();
        $this->setupParamsPlugin();
        $this->setupFormPlugin();
        $this->setupGridControllerPlugin();
        $this->setupControllerFilePlugin();

    }

    function setupForwardPlugin()
    {
    	$this->mockForwardPlugin = \Mockery::mock('Zend\Mvc\Controller\Plugin\Forward');
    	$this->controller->getPluginManager()->setAllowOverride(true);
    	$this->controller->getPluginManager()->setService('forward', $this->mockForwardPlugin);
    	$this->mockForwardPlugin->shouldReceive('setController')->with($this->controller);
    }

    function setupParamsPlugin()
    {
    	$this->mockParamsPlugin = \Mockery::mock('Zend\Mvc\Controller\Plugin\Params');
    	$this->controller->getPluginManager()->setAllowOverride(true);
    	$this->controller->getPluginManager()->setService('params', $this->mockParamsPlugin);
    	$this->mockParamsPlugin->shouldReceive('setController')->with($this->controller);

    	$this->mockParamsPlugin->shouldReceive("__invoke")->zeroOrMoreTimes()->withNoArgs()->andReturn($this->mockParamsPlugin);
    }

    function setupFormPlugin()
    {
    	$this->mockFormPlugin = \Mockery::mock('\Application\Plugin\FormPlugin');
    	$this->controller->getPluginManager()->setAllowOverride(true);
    	$this->controller->getPluginManager()->setService('formPlugin', $this->mockFormPlugin);
    	$this->mockFormPlugin->shouldReceive('setController')->with($this->controller);
    }
    
    function setupControllerFilePlugin()
    {
    	$this->mockFilePlugin = \Mockery::mock('\Application\Controller\Plugin\FilePlugin');
    	$this->controller->getPluginManager()->setAllowOverride(true);
    	$this->controller->getPluginManager()->setService('FilePlugin', $this->mockFilePlugin);
    	$this->mockFilePlugin->shouldReceive('setController')->with($this->controller);
    }

    function setupGridControllerPlugin()
    {
    	$this->mockGridControllerPlugin = \Mockery::mock('\Grid\Plugin\GridControllerPlugin');
    	$this->controller->getPluginManager()->setAllowOverride(true);
    	$this->controller->getPluginManager()->setService('gridControllerPlugin', $this->mockGridControllerPlugin);
    	$this->mockGridControllerPlugin->shouldReceive('setController')->with($this->controller);
    }


    protected function dispatch($actionName){
        $this->routeMatch->setParam('action', $actionName);
        return $this->controller->dispatch($this->request, $this->response);
    }

    protected function setPost(array $postVariables){
        $this->request->setMethod('POST');
        $this->request->setPost(new \Zend\Stdlib\Parameters($postVariables));
        foreach ($postVariables as $key=>$value) {
            $this->mockParamsPlugin->shouldReceive('fromPost')->with($key)->andReturn($value);
        }
        $this->mockParamsPlugin->shouldReceive('fromPost')->with()->andReturn($postVariables);
    }

    protected function setGet(array $getVariables){
        foreach ($getVariables as $key=>$value) {
            $this->mockParamsPlugin->shouldReceive('fromQuery')->with($key)->andReturn($value);
        }
        $this->request->setQuery(new \Zend\Stdlib\Parameters($getVariables));
    }

    protected function assertFormPluginGetsCalled($expectedForm)
    {
    	$this->mockFormPlugin->shouldReceive('handleFormPost')->once()->with($expectedForm);
    }

    protected function assertFormPluginGetsCalledAndReturns($expectedForm,$response)
    {
    	$this->mockFormPlugin->shouldReceive('handleFormPost')->once()->with($expectedForm,\Mockery::any())->andReturn($response);
    }

    protected function assertFormPluginGetsCalledAndModifiesParameters($expectedForm, $extraParameters, callable $modifyFunction)
    {
    	$this->mockFormPlugin->shouldReceive('handleFormPost')->once()->with($expectedForm,$extraParameters)->andReturnUsing($modifyFunction);
    }

    protected function excerciseFormPostSuccessMethod($action,$formValues,&$extraParameters = null)
    {
    	$methodToCall = $action.'FormPostSucess';
    	return $this->controller->$methodToCall($formValues,$extraParameters);
    }

    public function assertRedirectTo($result, $uri, $message = '')
    {
        $this->assertEquals($this->response,$result,'response object is returned on redirect');
        $location = $this->response->getHeaders()->get('location');

        if (!$location)
        {
            $this->fail($message);
        }

        $this->assertSame($uri, $location->getFieldValue(), $message);
    }

    protected function expectForward($expectedControllerAliasOrClass,$expectedAction)
    {
        $this->mockForwardPlugin->shouldReceive('dispatch')->once()->with($expectedControllerAliasOrClass,array('action'=>$expectedAction))->andReturn(self::FORWARDED);
    }

    function assertSynchronizesDb()
    {
        $this->assertInstanceOf('\Ifp\Sync\StartWithDbSync', $this->controller);
    }
    function assertDoesNotSynchronizeDb()
    {
        $this->assertNotInstanceOf('\Ifp\Sync\StartWithDbSync', $this->controller);
    }
}
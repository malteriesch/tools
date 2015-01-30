<?php
namespace UnitTest\Lib\TestCase;

abstract class RenderableTestCase extends \UnitTest\Lib\BaseTestCase
{
    use \UnitTest\Lib\Traits\HtmlAssertions;

    protected $renderer;
    protected $resolver;
    protected $mockErrorBox;
    protected $mockFormHelper;


    function Setup()
    {
        \Mockery::getConfiguration()->allowMockingNonExistentMethods(true);
        $this->resolver = new \Zend\View\Resolver\TemplatePathStack();


        foreach ((array) $this->getTemplatePaths() as $path) {
            $this->resolver->addPath($path);
        }

        $this->renderer = $this->createRenderer();
        $this->renderer->setResolver($this->resolver);

        $this->setFormHelpers();
    }

    abstract function getTemplatePaths();

    protected function createRenderer()
    {
        if ($mockedPlugins = $this->getMockedPlugins() ) {
            return \Mockery::mock('\Zend\View\Renderer\PhpRenderer['.implode(',',$mockedPlugins).']');
        }
        return new \Zend\View\Renderer\PhpRenderer();
    }

    protected function getMockedPlugins(){
        return [];
    }

    protected function setFormHelpers()
    {
        $formhelperConfig = new \Zend\Form\View\HelperConfig();
        $formhelperConfig->configureServiceManager($this->renderer->getHelperPluginManager());
//        var_dump($this->renderer->getHelperPluginManager()); exit;
        $this->renderer->getHelperPluginManager()->setInvokableClass('AttributedFormElement','\Ifp\Form\View\Helper\FormElement');
//        $this->renderer->getHelperPluginManager()->setInvokableClass('form','\Zend\Form\View\Helper\Form');
        $this->mockErrorBox = \Mockery::mock('\Ifp\Form\View\Helper\FormErrors');
        $this->renderer->getHelperPluginManager()->setService('ErrorBox',$this->mockErrorBox);
        
//        $this->mockFormHelper = \Mockery::mock('\Zend\Form\View\Helper\Form');
//        $this->renderer->getHelperPluginManager()->setService('form', $this->mockFormHelper);

    }
    
    protected function expectAngular($expectedApplication, $expectedDependencies)
    {
        $mockAngularViewHelperPlugin = \Mockery::mock('Application\View\Helper\AngularViewHelperPlugin');
        $mockAngularViewHelperPlugin->shouldReceive('loadApplication')->once()->with($expectedApplication, $expectedDependencies);
        $this->renderer->shouldReceive('AngularViewHelperPlugin')->andReturn($mockAngularViewHelperPlugin);
    }

}
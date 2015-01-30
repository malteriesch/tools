<?php
namespace UnitTest\Lib\TestCase;



abstract class FormTestCase extends \UnitTest\Lib\BaseTestCase
{
    protected $form;

    protected function assertLabel($expectedLabel,$elementName){
        $this->assertEquals( $expectedLabel, $this->form->get($elementName)->getLabel() );
    }

    protected function assertType($expectedType,$elementName){
        $this->assertEquals( $expectedType, $this->form->get($elementName)->getAttribute('type') );
    }

    protected function assertPlaceHolder($expectedPlaceHolder,$elementName){
    	$this->assertEquals( $expectedPlaceHolder, $this->form->get($elementName)->getAttribute('placeholder') );
    }

    protected function assertValue($expectedValue,$elementName){
        $this->assertEquals( $expectedValue, $this->form->get($elementName)->getValue() );
    }


    protected function assertErrorMessages($messages,$elementName) {
        $this->assertEquals($messages,$this->form->get($elementName)->getMessages());
    }

    protected function assertNoErrorMessages($elementName) {
        $this->assertEquals(array(),$this->form->get($elementName)->getMessages());
    }

    protected function assertRadioOptions($expectedOptions, $elementName){
		$this->assertEquals($expectedOptions, $this->form->get($elementName)->getValueOptions());
    }

    protected function assertFormData($expectedFormDataSet, $actualFormData)
    {
        foreach ( $expectedFormDataSet as $expectedKey => $expectedValue ) {
            $this->assertEquals( $expectedValue, $actualFormData[$expectedKey] );
        }

    }
    protected function assertAction($expectedAction)
    {
        $this->assertEquals( $expectedAction, $this->form->getAttribute('action') );
    }

    protected function assertFilterChain( $expectedFilterOrFilters, $elementName)
    {
    	if (!is_array($expectedFilterOrFilters)) {
    		$expectedFilterOrFilters = array($expectedFilterOrFilters);
    	}
    	$filterChain = $this->form->getInputFilter()->get($elementName)->getFilterChain()->getFilters()->toArray();

    	$this->assertEquals(count($expectedFilterOrFilters),count($filterChain),'filterChain has expected length');

    	foreach($expectedFilterOrFilters as $index=>$filterClass) {
    		$this->assertInstanceOf($filterClass, $filterChain[$index]);
    	}
    }

    abstract protected function getForm();

    function setUp(){
        $this->form = $this->getForm();
    }
}
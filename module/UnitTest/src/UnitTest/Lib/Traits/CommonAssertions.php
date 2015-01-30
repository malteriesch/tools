<?php

namespace UnitTest\Lib\Traits;

trait CommonAssertions
{

   public function assertEqualSets($expected,$actual,$message='')
   {
       sort($expected);
       sort($actual);
       $this->assertEquals($expected,$actual,$message);
   }
   
   function assertGettersAndSetters($object, $methodstubs)
   {
       $values = [];
       foreach ($methodstubs as $methodStub){
           $setterMethod="set$methodStub";
           $uniqueValue=uniqid("");
           $values[$methodStub]=$uniqueValue;
           $object->$setterMethod($uniqueValue);
           
       }
       
       foreach ($methodstubs as $methodStub){
           $getterMethod="get$methodStub";
           $object->$setterMethod($uniqueValue);
           $this->assertSame($values[$methodStub],$object->$getterMethod());
       }
   }
   
    function assertAllGettersAndSetters($object)
    {
        $methodStubs = [];
        
        $reflectionClass = new \ReflectionClass($object);
        foreach($reflectionClass->getMethods() as $reflectionMethod){
           $method = $reflectionMethod->getShortName();
           
           if (strpos($method,'get')===0){
               $stub=  substr($method, 3);
               if ($reflectionClass->hasMethod('set'.$stub)) {
                   $methodStubs[]= $stub;
               }
           }
           
        }
       
        $this->assertGettersAndSetters($object,$methodStubs);
    }
   
}
<?php
namespace UnitTest\Lib\Traits;

trait ExceptionAssertions {

   protected function assertException($expectedMessage, callable $callingWrapper) 
   {
       try {
            $callingWrapper();
            $this->fail("Exception is thrown");
            
        } catch(\Exception $exception) {
            $this->assertEquals($expectedMessage,$exception->getMessage());
        }
   }
}
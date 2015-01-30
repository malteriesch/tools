<?php
namespace UnitTest\Lib;
class Utils {

    static $toCompare;

    public static function arrayDiff($first,$second,$path="")
    {
        $firstKeys = array_keys($first);
        $secondKeys = array_keys($second);
        if ($firstKeys !== $secondKeys){
            echo "[$path] : keys are different";
        }
        foreach ($firstKeys as $key) {
            if ($first[$key]!==$second[$key]) {

                 if (is_array($first[$key]) && is_array($second[$key])){
                     self::arrayDiff($first[$key],$second[$key],$path."/$key");
                 } else {
                     echo "\n[$path] : values are different for key $key:\n\n";
                     echo "First:\n";
                     print_r($first[$key]);
                     echo "\nSecond:\n";
                     print_r($second[$key]);
                 }
            }
        }
        exit();
    }

    public static function storeDiffArray($toCompare)
    {
        self::$toCompare = $toCompare;
    }

    /**
     * Wraps a function call that ends up in dreaded "Mockery\Exception\NoMatchingExpectationException: No matching handler found for .." Mockery failure
     *
     * It enables, in conjunction with storeDiffArray(), to display the difference in the arguments of complex arrays in method calls
     *
     * It works by trapping the error and the performing a diff between two arrays, the one provided and one specified in the tesetd code by storeDiffArray
     *
     * Usage:
     *
     * Temporarily, when confronted with the above exception, exchange (in the Unit Test):
     * $foo = $testObject->doStuffThatFails();
     * with:
     * \UnitTest\Lib\Utils::wrapFailingCall($expectedComplexArray,function(){
     *       $foo = $testObject->doStuffThatFails();
     * });
     *
     * And in the tested code
     * class Bla {
     *     function doStuffThatFails() {
     *         //...
     *         \UnitTest\Lib\Utils::storeDiffArray($complexArray);//needs to be removed when fininshing debugging, command line php unit will fail otherwise
     *         $someObjectThatIsActuallyMocked->methodCall($complexArray);
     *         //...
     *     }
     * }
     *
     * @param array $expectedArray The complex array of the method call which fails
     * @param Callable $lambda the wrapping of the test code
     */
    public static function wrapFailingCall(array $expectedArray,$lambda)
    {
        try{
            $lambda();
        }catch(\Mockery\Exception\NoMatchingExpectationException $e) {
            self::arrayDiff(self::$toCompare, $expectedArray);
        }
    }
}
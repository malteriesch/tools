<?php
namespace UnitTest\Lib;
class Mockery
{
    public static function arrayInAnyOrder($expected)
    {
        $return = new Mockery\Matcher\InAnyOrderMatcher($expected);
        return $return;
    }
    public static function stringContains($expected)
    {
        $return = new Mockery\Matcher\SubstringMatcher($expected);
        return $return;
    }
}
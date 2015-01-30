<?php
namespace UnitTest\Lib\Mockery\Matcher;

class InAnyOrderMatcher extends \Mockery\Matcher\MatcherAbstract
{
    public function __construct($expected)
    {
        sort($expected);
        parent::__construct($expected);
    }
 
    public function match(&$actual)
    {
        $copyOfActual = $actual;
        sort($copyOfActual);
        return $copyOfActual==$this->_expected;
    }
    
    /**
     * Return a string representation of this Matcher
     *
     * @return string
     */
    public function __toString()
    {
        return '<InAnyOrder>';
    }
    
}
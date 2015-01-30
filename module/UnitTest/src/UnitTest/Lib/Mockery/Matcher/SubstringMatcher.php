<?php
namespace UnitTest\Lib\Mockery\Matcher;

class SubstringMatcher extends \Mockery\Matcher\MatcherAbstract
{
    public function __construct($expected)
    {
        if (is_string($expected)) {
            $expected = [$expected];
        }
        parent::__construct($expected);
    }
    /**
     * Check if the actual value matches the expected.
     *
     * @param mixed $actual
     * @return bool
     */
    public function match(&$actual)
    {
        foreach ($this->_expected as $expectedContainedString) {
            if (strpos($actual,$expectedContainedString)===false){
                return false;
            }
        }
        return true;
    }
    
    /**
     * Return a string representation of this Matcher
     *
     * @return string
     */
    public function __toString()
    {
        return '<Substring>';
    }
    
}
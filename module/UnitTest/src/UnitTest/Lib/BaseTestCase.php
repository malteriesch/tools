<?php

namespace UnitTest\Lib;

require_once( 'tests/config.php' );

use org\bovigo\vfs\vfsStreamWrapper;

abstract class BaseTestCase extends \PHPUnit_Framework_TestCase {

    use \UnitTest\Lib\Traits\CommonAssertions;
    /**
     * @var \UnitTest\Lib\ObjectMother;
     */
    protected $objectMother;

    function __construct() {
        parent::__construct();
        vfsStreamWrapper::register();
        \Mockery::getConfiguration()->allowMockingNonExistentMethods(false);
        $this->objectMother = new \UnitTest\Lib\ObjectMother($this);
        $this->onCreate();
    }

    
    public function teardown() {
        \Mockery::close();
    }

    protected function onCreate()
    {
        
    }
}
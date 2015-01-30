<?php
namespace UnitTest\Lib;

class ObjectMother {
    
    protected $testCase;
    
    function __construct(\PHPUnit_Framework_TestCase $testCase){
        $this->testCase=$testCase;
    }
    
    /**
     * 
     * @return \Zend\Http\PhpEnvironment\Request
     */
    function createRefererRequest($expectedUrl)
    {
        $headers = new \Zend\Http\Headers();
        $headers->addHeaderLine('Referer',$expectedUrl);

        $request = new \Zend\Http\PhpEnvironment\Request();
        $request->setHeaders($headers);
        
        return $request;
    }
    
    function createGridQueryBuilder()
    {
        $dbConfig = include(APPLICATION_PATH .'/config/autoload/db-config.php');
        return new \Grid\Service\GridQueryBuilder(new \Grid\Service\DbConfiguration( $dbConfig['dbConfigurationData'] ));
    }
    
    function createMockCycleCodeInCyclemanagementService($mockCycleManagementService)
    {
        $mockCycleCode = \Mockery::mock('CycleManagement\Model\CycleCode');
        $mockCycleManagementService->shouldReceive('getCurrentCycle')->andReturn($mockCycleCode);
        return $mockCycleCode;
    }
    
    
}
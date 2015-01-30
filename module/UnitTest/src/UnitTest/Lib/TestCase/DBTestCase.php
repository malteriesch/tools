<?php
namespace UnitTest\Lib\TestCase;
/**
 *
 * Abstract base class for database specific tests
 *
 * The test will redefine the default database connection that models and services use to the database unit_tests, with the user unit_tests and the password unit_tests
 * It is asumed that the table structure is already present, however the data is volatile and will be set up by the tests themselves.
 *
 * Each test can have an associated data folder that can hold PSV formatted files
 * @see UnitTest_Lib_PsvParser
 *
 * The data folder is assigend like this:
 *
 * tests/data/unit/application/max/models/ContactAppModelTest/test_getAssignedTeamListContacts.psv
 * correlates to
 * tests/data/unit/application/max/models/ContactAppModelTest
 *
 * below this will be called SETUP_FOLDER
 *
 * Inside this folder there may also be a folder 'expected'
 * ( tests/data/unit/application/max/models/ContactAppModelTest/expected )
 * which can hold expectation PSV files
 *
 * below this will be called EXPECTATION_FOLDER
 *
 * By default a test name test_Foo will correlate to a setup psv file
 * tests/data/unit/application/max/models/ContactAppModelTest/test_foo.psv
 * and and expectation psv file
 * tests/data/unit/application/max/models/ContactAppModelTest/expected/test_foo.psv
 *
 * These file only will be used if they exist,
 * it is possible to define PSV both inline and have multiple or shared setup files and expectations
 *
 *
 *
 */

use \UnitTest\Lib\PsvParser;
use \UnitTest\Lib\PsvDataSet;

/**
 * pear channel-discover pear.php-tools.net
 * pear install pat/vfsStream-alpha
 */
abstract class DBTestCase extends \UnitTest\Lib\BaseTestCase
{

    /**
     * @var \UnitTest\Lib\DatabaseHelper
     */
    protected $databaseHelper;


    
    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $zendDb;
    
    
    public function teardown() {
        $this->databaseHelper->onTearDown();
        parent::teardown();
    }
        
    public function setUp() {
        parent::setUp();
        $this->databaseHelper->onSetup();
    }
        

    /**
     * Assert that a given table has a given amount of rows
     *
     * @param string $tableName Name of the table
     * @param int $expected Expected amount of rows in the table
     * @param string $message Optional message
     */
    public function assertTableRowCount($tableName, $expected, $message = '')
    {
        $constraint = new \UnitTest\Lib\PhpUnit\Constraint\TableRowCount($tableName, $expected);
        $actual = $this->getConnection()->getRowCount($tableName);
        
        self::assertThat($actual, $constraint, $message);
    }

    /**
     * Asserts that two given tables are equal.
     *
     * @param PHPUnit_Extensions_Database_DataSet_ITable $expected
     * @param PHPUnit_Extensions_Database_DataSet_ITable $actual
     * @param string $message
     */
    public function assertTablesEqual(\PHPUnit_Extensions_Database_DataSet_ITable $expected, \PHPUnit_Extensions_Database_DataSet_ITable $actual, $message = '')
    {
        $constraint = new \PHPUnit_Extensions_Database_Constraint_TableIsEqual($expected);

        self::assertThat($actual, $constraint, $message);
    }

    /**
     * Asserts that two given datasets are equal.
     *
     * @param PHPUnit_Extensions_Database_DataSet_ITable $expected
     * @param PHPUnit_Extensions_Database_DataSet_ITable $actual
     * @param string $message
     */
    public static function assertDataSetsEqual(\PHPUnit_Extensions_Database_DataSet_IDataSet $expected, \PHPUnit_Extensions_Database_DataSet_IDataSet $actual, $message = '')
    {
        $constraint = new \PHPUnit_Extensions_Database_Constraint_DataSetIsEqual($expected);

        self::assertThat($actual, $constraint, $message);
    }


    /////
    protected function _getDataFolder()
    {
        return __DIR__.'/data/';
    }

    private  function _arrayToDataSet( array $toConvert, $tableName = 'theTable' )
    {
        $columns = array();

        if (isset($toConvert[0])) {
            $columns = array_keys($toConvert[0]);
        }

        $metaData = new \PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData( $tableName, $columns );
        $table    = new \PHPUnit_Extensions_Database_DataSet_DefaultTable( $metaData );

        foreach ($toConvert AS $row) {
            $table->addRow($row);
        }
        return $table;
    }



    private function _getExpectationContents( $postfix = false )
    {

        $file = $this->_getDataFolder().'expected/' . $this->getName();
        if ( $postfix ) {
            $file .= "-$postfix";
        }
        $file .= '.psv';
        return file_get_contents( $file );
    }

    /**
     * Executes some sql against test database
     * @param string $sql
     */
    protected function executeSql( $sql ){
        $this->getConnection()->getConnection()->query( $sql );
    }
    /**
     * get some sql results from test database
     * @param string $sql
     */
    protected function getSql( $sql ){
        return $this->getConnection()->getConnection()->query( $sql )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     *
     * Will instruct the databse such that the next autiincremented value for a table is as specified
     * @param string $table
     * @param int $nextIncrement
     */
    protected function setAutoIncrementAs( $table, $nextIncrement )
    {
        $this->executeSql( "ALTER TABLE $table AUTO_INCREMENT = $nextIncrement" );
    }

    /**
     *
     * Asserts that DB tables are in the state described in the expectation file in EXPECTATION_FOLDER. if $postfix of 'moo' is provided then for test function test_foo the file will be test_foo-moo.psv
     * The format inside the file is as in assertTableState
     * @param $postfix optional postfix parameter, or false
     * @param $message optional assertion message
     * @see UnitTest_Lib_PsvParser::parsePsvTree for the format
     */
    protected function assertTableStateFromFile( $fileName = false, $message='')
    {
        $this->assertTableState($file = $this->_getDataFolder(). $fileName, $message);
    }

    /**
     *
     * Asserts that DB tables are in the state described
     * @param string $sExpectedPsv the PSV formatted string with the tables and their expected values.
     * @param string $message optional assertion message
     * @see UnitTest_Lib_PsvParser::parsePsvTree for the format
     */
    protected function assertTableState( $sExpectedPsv, $message = '' )
    {
        $expectedDataSet = new \UnitTest\Lib\PsvDataSet( $sExpectedPsv );
        foreach ( $expectedDataSet->getTableNames() as $tableName ) {
            $this->assertTablesEqual($expectedDataSet->getTable( $tableName ), $this->getConnection()->createDataSet()->getTable( $tableName ), $message );
        }
    }

    protected function getDateColumns($tableName){
        $columns = $this->getSql("SELECT COLUMN_TYPE,COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema='".TEST_DB_NAME."' AND table_name='$tableName'");
        $dateColumns = array();
        foreach($columns as $columnRow) {
            if (in_array(strtolower($columnRow['COLUMN_TYPE']),['date','datetime'])!==false) {
                $dateColumns[] = $columnRow['COLUMN_NAME'];
            }
        }
        return $dateColumns;
    }
    /**
     *
     * Asserts that DB tables are in the state described
     * @param string $sExpectedPsv the PSV formatted string with the tables and their expected values.
     * @param string $message optional assertion message
     * @see UnitTest_Lib_PsvParser::parsePsvTree for the format
     */
    protected function assertTableStateContains( $sExpectedPsv, $message = '' )
    {
        $expectedDataSet = new PsvDataSet( $sExpectedPsv );
        foreach ( $expectedDataSet->getTableNames() as $tableName ) {
            $actualData   = $this->getConnection()->createDataSet()->getTable( $tableName );

            $dateColumns = $this->getDateColumns($tableName);

            $expectedData    = $expectedDataSet->getTable( $tableName );
            $expectedColumns = $expectedData->getTableMetaData()->getColumns();
            $filteredActualData = array();
            for ($rowNumber = 0; $rowNumber< $actualData->getRowCount();$rowNumber++) {
                $currentRow = $actualData->getRow($rowNumber);
                foreach ($expectedColumns as $expectedColumn) {
                    if (in_array($expectedColumn,$dateColumns) && !is_null($currentRow[ $expectedColumn ])) {
                        $currentRow[ $expectedColumn ] = date("Y-m-d",strtotime($currentRow[ $expectedColumn ]));
                    }
                    $filteredActualData[$rowNumber][ $expectedColumn ] = $currentRow[ $expectedColumn ];
                }
            }

            $this->assertTablesEqual($expectedDataSet->getTable( $tableName ), $this->_arrayToDataSet( $filteredActualData, $tableName ), $message );
        }
    }

    /**
     *
     * asserts that an array has the content as specified per psv specified in the file in EXPECTATION_FOLDER. if $postfix of 'moo' is provided then for test function test_foo the file will be test_foo-moo.psv
     * @param $actual the array to be compared against
     * @param $postfix optional postfix parameter, or false
     * @param $message optional assertion message
     */
    protected function assertEqualsPsvFromFile( array $actual, $postfix = false, $message='' )
    {
        $this->assertEqualsPsv($this->_getExpectationContents( $postfix ), $actual, $message);
    }

    /**
     *
     * asserts that an array has the content as specified per psv
     * @param string the expected array contents in psv format
     * @param array $actual the array to be compared against
     * @param string $message optional assertion message
     * @see UnitTest_Lib_PsvParser::parsePsv for the format
     */
    protected function assertEqualsPsv($expectedPsv, array $actual, $message = "" )
    {
        $expectedDataSet = $this->_arrayToDataSet( $this->_getPsvParser()->parsePsv( $expectedPsv ) );
        $actualDataSet   = $this->_arrayToDataSet( $actual );
        $this->assertTablesEqual( $expectedDataSet, $actualDataSet, $message );
    }

    /**
     *
     * asserts that an array has the content as specified per psv
     * @param string the expected array contents in psv format
     * @param array $actual the array to be compared against
     * @param string $message optional assertion message
     * @see UnitTest_Lib_PsvParser::parsePsv for the format
     */
    protected function assertEqualsSingleRowPsv($expectedPsv, array $actual, $message = "" )
    {
        $expectedDataSet = $this->_arrayToDataSet( $this->_getPsvParser()->parsePsv( $expectedPsv ) );
        $actualDataSet   = $this->_arrayToDataSet( array($actual) );
        $this->assertTablesEqual( $expectedDataSet, $actualDataSet, $message );
    }

    /**
     *
     * Similar to assertEqualsPsvFromFile, but only the columns specified in the psv is compared against
     * @param array $actual the array to be compared against
     * @param string $postfix  optional postfix parameter, or false
     * @param string $message $message optional assertion message
     */
    protected function assertContainsPsvFromFile( array $actual, $postfix = false, $message = "" )
    {
        $this->assertContainsPsv($this->_getExpectationContents( $postfix ), $actual, $message);
    }

    /**
     *
     * Similar to assertEqualsPsv, but only compares against the columns specified in the psv
     * @param string $expectedPsv the psv string
     * @param array $actual the array to be compared against
     * @param $message optional assertion message
     */
    protected function assertContainsPsv($expectedPsv, array $actual, $message = "" )
    {
        $expectedArray    = $this->_getPsvParser()->parsePsv( $expectedPsv );

        $expectedColumns  = array_keys( $expectedArray[0] );

        $filteredActual = array();
        foreach ( $actual as $actualRow ) {
            $newRow = array();
            foreach( $expectedColumns as $expectedColumn ) {
                $newRow[ $expectedColumn ] = $actualRow[ $expectedColumn ];
            }
            $filteredActual[] = $newRow;
        }
//print_r($filteredActual);die;
        $expectedDataSet = $this->_arrayToDataSet( $expectedArray );
        $actualDataSet   = $this->_arrayToDataSet( $filteredActual );
       
        
        $this->assertTablesEqual( $expectedDataSet, $actualDataSet, $message );
    }

    /**
     *
     * Sets up the data specified in the file, which is located in the SETUP_FOLDER
     * @param $psvFile
     */
    protected function setupPsvTestDataFromFile( $psvFile )
    {
        $this->databaseHelper->setupPsvTestDataFromFile( $psvFile );
    }

    protected function setupPsvTestDataFromContent( $psvContent )
    {
        $this->databaseHelper->setupPsvTestDataFromContent( $psvContent );
    }

    function __construct()
    {
        parent::__construct();
        
        $this->zendDb = \UnitTest\Lib\DatabaseConnector::getConnection($this->getZendTestDbConfig());
        $this->databaseHelper = new \UnitTest\Lib\DatabaseHelper($this->getTestPdo());
    }
    

    public function getZendTestDbConfig()
    {
        return [
                  'driver'=> 'Pdo',
                  'dsn' => 'mysql:dbname='.TEST_DB_NAME.';host='.TEST_DB_SERVER,
                  'username' => TEST_DB_USERNAME,
                  'password' => TEST_DB_PASSWORD,
                  'driver_options' => array(
                          \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
                  )];
    }

    public function getPdoTestDbConfig()
    {
        $config = $this->getZendTestDbConfig();
        return [
                  'dsn' => $config['dsn'],
                  'username' => $config['username'],
                  'password' => $config['password']];
    }

    public function getConnection()
    {
        return $this->databaseHelper->getConnection();
    }

    protected function _getPsvParser()
    {
        return new PsvParser();
    }


    protected function getTruncatedNow()
    {
        return date("Y-m-d");
    }

    protected function getTestZendDb()
    {
        return $this->zendDb;
    }

    protected function getTestLogDatabaseAdapter()
    {
        $mockLogDatabaseFacade = \Mockery::mock('Ifp\Db\DatabaseFacade');
        $mockLogDatabaseFacade->shouldIgnoreMissing();
        $mockRequest = \Mockery::mock('Zend\Http\PhpEnvironment\Request');
        $mockRequest->shouldIgnoreMissing();
        return new \ActivityLog\Service\LogDatabaseAdapter($this->getTestZendDb(),$mockLogDatabaseFacade,$mockRequest,['user_id'=>0,'username'=>'foo']);
    }
    
    protected function getTestDatabaseFacade()
    {
        return new \Ifp\Db\DatabaseFacade($this->getTestZendDb());
    }

    /**
     * @return \PDO
     */
    protected function getTestPdo()
    {
        return $this->zendDb->getDriver()->getConnection()->getResource();
    }


}

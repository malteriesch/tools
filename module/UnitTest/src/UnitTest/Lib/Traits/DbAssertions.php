<?php
namespace UnitTest\Lib\Traits;
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

/**
 * pear channel-discover pear.php-tools.net
 * pear install pat/vfsStream-alpha
 */

trait DbAssertions
{

//     const TEST_DB_NAME               = 'unit_tests';
//     const TEST_DB_USERNAME           = 'unit_tests';
//     const TEST_DB_PASSWORD           = 'unit_tests';

    protected $dataFolder            = null;
    protected $psvParser             = null;



    protected function _getDataFolder()
    {
        $reflectionClass = new ReflectionClass($this);
        $testFile        = str_replace( '.php', '', $reflectionClass->getFileName() );
        $testFolder      = realpath( APPLICATION_PATH.'/../tests' );
        return $testFolder.'/data'.str_replace( $testFolder, '', $testFile ).'/';
    }

    private  function _arrayToDataSet( array $toConvert, $tableName = 'theTable' )
    {
        $columns = array();

        if (isset($toConvert[0])) {
            $columns = array_keys($toConvert[0]);
        }

        $metaData = new PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData( $tableName, $columns );
        $table    = new PHPUnit_Extensions_Database_DataSet_DefaultTable( $metaData );

        foreach ($toConvert AS $row) {
            $table->addRow($row);
        }
        return $table;
    }

    private function _createPsvDataSetFromContent( $psv )
    {
        return new UnitTest_Lib_PsvDataSet( $psv );
    }

    private function _createPsvDataSetFromFile( $psvFile )
    {
        return $this->_createPsvDataSetFromContent( file_get_contents( $psvFile ) );
    }

    private function _getExpectationContents( $postfix = false )
    {

        $file = $this->dataFolder.'expected/' . $this->getName();
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
    protected function assertTableStateFromFile( $postfix = false, $message='')
    {
        $this->assertTableState($this->_getExpectationContents( $postfix ), $message);
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
        $expectedDataSet = new UnitTest_Lib_PsvDataSet( $sExpectedPsv );
        foreach ( $expectedDataSet->getTableNames() as $tableName ) {
            $this->assertTablesEqual($expectedDataSet->getTable( $tableName ), $this->getConnection()->createDataSet()->getTable( $tableName ), $message );
        }
    }

    protected function getDateColumns($tableName){
        $columns = Zend_Db_Table_Abstract::getDefaultAdapter()->query("SELECT COLUMN_TYPE,COLUMN_NAME FROM information_schema.COLUMNS WHERE table_schema='".self::TEST_DB_NAME."' AND table_name='$tableName'")->fetchAll();
        $dateColumns = array();
        foreach($columns as $columnRow) {
            if (strpos(strtolower($columnRow['COLUMN_TYPE']),'date')!==false) {
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
        $expectedDataSet = new UnitTest_Lib_PsvDataSet( $sExpectedPsv );
        foreach ( $expectedDataSet->getTableNames() as $tableName ) {
            $actualData   = $this->getConnection()->createDataSet()->getTable( $tableName );

            $dateColumns = $this->getDateColumns($tableName);

            $expectedData    = $expectedDataSet->getTable( $tableName );
            $expectedColumns = $expectedData->getTableMetaData()->getColumns();
            $filteredActualData = array();
            for ($rowNumber = 0; $rowNumber< $actualData->getRowCount();$rowNumber++) {
                $currentRow = $actualData->getRow($rowNumber);
                foreach ($expectedColumns as $expectedColumn) {
                    if (in_array($expectedColumn,$dateColumns)) {
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
        $expectedDataSet = $this->_arrayToDataSet( $this->psvParser->parsePsv( $expectedPsv ) );
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
        $expectedDataSet = $this->_arrayToDataSet( $this->psvParser->parsePsv( $expectedPsv ) );
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
    	$expectedArray    = $this->psvParser->parsePsv( $expectedPsv );

    	$expectedColumns  = array_keys( $expectedArray[0] );

    	$filteredActual = array();
    	foreach ( $actual as $actualRow ) {
    		$newRow = array();
    		foreach( $expectedColumns as $expectedColumn ) {
                $newRow[ $expectedColumn ] = $actualRow[ $expectedColumn ];
    		}
    		$filteredActual[] = $newRow;
    	}

        $expectedDataSet = $this->_arrayToDataSet( $expectedArray );
        $actualDataSet   = $this->_arrayToDataSet( $filteredActual );
        $this->assertTablesEqual( $expectedDataSet, $actualDataSet, $message );
    }

    /**
     *
     * Sets up the data specified in the file, which is located in the SETUP_FOLDER
     * @param $psvFile
     */
    protected function setupPsvTestData( $psvFile )
    {
    	$this->databaseTester = NULL;

        $this->getDatabaseTester()->setSetUpOperation($this->getSetUpOperation());
        $this->getDatabaseTester()->setDataSet( $this->_createPsvDataSetFromFile ( $this->dataFolder . $psvFile ) );
        $this->getDatabaseTester()->onSetUp();
    }

    protected function setupPsvTestDataFromContent( $psvContent )
    {
    	$this->databaseTester = NULL;

        $this->getDatabaseTester()->setSetUpOperation($this->getSetUpOperation());
        $this->getDatabaseTester()->setDataSet( $this->_createPsvDataSetFromContent( $psvContent ) );
        $this->getDatabaseTester()->onSetUp();
    }


    function setUpBeforeClass()
    {
        parent::__construct();
        $this->dataFolder = $this->_getDataFolder();
        $this->psvParser  = new UnitTest_Lib_PsvParser();

        Zend_Db_Table_Abstract::setDefaultAdapter( new Zend_Db_Adapter_Pdo_Mysql( array( "dbname"   => self::TEST_DB_NAME,
                                                                                         "username" => self::TEST_DB_USERNAME,
                                                                                         "password" => self::TEST_DB_PASSWORD,
                                                                                         "host"     => STR_DB_SERVER
                                                                     ) ) );


        $frontController = Zend_Controller_Front::getInstance();
        $container =$frontController->getParam('bootstrap')->getContainer();
        $container->db =Zend_Db_Table_Abstract::getDefaultAdapter();
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit/Extensions/Database/PHPUnit_Extensions_Database_TestCase::getDataSet()
     */
    public function getDataSet()
    {

        $file = $this->dataFolder.$this->getName().'.psv';
        if ( file_exists( $file ) ) {
            return $this->_createPsvDataSetFromFile ( $file );
        } else {
            return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
        }
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit/Extensions/Database/PHPUnit_Extensions_Database_TestCase::getConnection()
     */
    public function getConnection()
    {
        return $this->createDefaultDBConnection( new PDO( 'mysql:dbname='.self::TEST_DB_NAME.';host='.STR_DB_SERVER, self::TEST_DB_USERNAME, self::TEST_DB_PASSWORD ), 'unit_tests');
    }
}

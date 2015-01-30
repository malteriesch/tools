<?php
namespace UnitTest\Lib;

class DatabaseHelper {

    /**
     * @var PHPUnit_Extensions_Database_ITester
     */
    protected $databaseTester;
    protected $testDbAcleDataInserter;

    protected $pdo;

    protected $tables=[];
    protected $mockedViews=[];
    protected $memoryTables=[];

    function __construct(\PDO $pdo = null)
    {
        if (is_null($pdo)) {
            $pdo = new \PDO( 'mysql:dbname='.TEST_DB_NAME.';host='.TEST_DB_SERVER, TEST_DB_USERNAME, TEST_DB_PASSWORD );
        }

        $this->pdo = $pdo;
        $this->setCheckForeignKeys(false);

        $tableColumn = 'Tables_in_'.TEST_DB_NAME.' (%_mem_parked)';
        try{
            foreach ($this->pdo->query("show tables like '%_mem_parked'")->fetchAll() as $table ){
                $this->pdo->query("DROP TABLE {$table[$tableColumn]};");
            }
        }catch(\Exception $e){

        }
        
        $this->testDbAcleDataInserter = new TestDbAcle\DataInserter($pdo);
        
        
    }
    
    public function setCheckForeignKeys($checkForeignKeys)
    {
        if ($checkForeignKeys){
            $value= 1;
        }else{
            $value= 0;
        }
        $this->pdo->query("SET FOREIGN_KEY_CHECKS = $value");
    }
    
    public function onSetup()
    {
        $this->memoryTables=[];
        $this->tables=[];
        $this->mockedViews=[];
    }
    public function onTearDown()
    {
        $this->unparkTables();
        $this->unmockViews();    
    }
    public function parkTable($table)
    {
        $this->tables[] = $table;
        try {
            $this->pdo->query("RENAME TABLE $table TO {$table}_parked");

        }catch (\Exception $e){

        }
        try{
            $this->pdo->query("CREATE TABLE $table LIKE {$table}_parked");
        }catch (\Exception $e){

        }
    }
    
    public function resetAllMockedViews(){
        $tableColumn = 'Tables_in_'.TEST_DB_NAME; 
        $tableViews = cQuery::getResultSet("SHOW FULL TABLES WHERE TABLE_TYPE LIKE 'VIEW' and $tableColumn like 'MOCKED_%';");

        foreach ( $tableViews as $view ) {
                $mockedView = $view[ $tableColumn ];
                $orginalView = str_replace("MOCKED_",'',$mockedView);
                $this->executeSql("RENAME TABLE $mockedView to $orginalView");
        }
    }
    
    public function unmockViews(){
        foreach ($this->mockedViews as $mockedView ) {
            $this->pdo->query("drop table $mockedView");
            $this->pdo->query("RENAME TABLE MOCKED_$mockedView to $mockedView");
        }
        $this->aMockedViews = array();
        
    }
	
    public function mockView( $viewName ) {
        $this->mockedViews[] = $viewName;
        $this->pdo->query("RENAME TABLE $viewName to MOCKED_$viewName");
        $this->pdo->query("CREATE TEMPORARY TABLE $viewName as select * from MOCKED_$viewName where 1=0");
    }
    
    public function parkTableToMemory($table)
    {
        if(isset($this->memoryTables[$table])){
            return;

        }

        $tableDescription = $this->pdo->query("show create table `$table`")->fetchAll()[0]['Create Table'];
        $tableDescription= str_replace('InnoDB','MEMORY',$tableDescription);
        $tableDescription= str_ireplace('TEXT','VARCHAR(255)',$tableDescription);


        $this->memoryTables[$table] = $table;
        try {
            $this->pdo->query("RENAME TABLE $table TO {$table}_mem_parked");

        }catch (\Exception $e){

        }

        $this->pdo->query("$tableDescription");

    }

    public function unparkTables()
    {
        foreach( $this->tables as $table) {
            $this->pdo->query("DROP TABLE IF EXISTS {$table}");
            try {
                $this->pdo->query("RENAME TABLE {$table}_parked TO $table");
            } catch (Exception $e){

            }
        }


        foreach( $this->memoryTables as $table) {
            $this->pdo->query("DROP TABLE IF EXISTS {$table}");
            try {
                $this->pdo->query("RENAME TABLE {$table}_mem_parked TO $table");
            } catch (Exception $e){
            }
        }
    }

    /**
     *
     * Sets up the data specified in the file, which is located in the SETUP_FOLDER
     * @param $psvFile
     */
    public function setupPsvTestDataFromFile( $psvFile )
    {
        $this->databaseTester = NULL;

        $this->getDatabaseTester()->setSetUpOperation($this->getSetUpOperation());
        $this->getDatabaseTester()->setDataSet( $this->_createPsvDataSetFromFile ( $this->_getDataFolder() . $psvFile ) );
        $this->getDatabaseTester()->onSetUp();
    }

    protected function parkTablesToMemory(\UnitTest\Lib\PsvDataSet $content)
    {
        foreach($content->getTableNames() as $table){
            $this->parkTableToMemory($table);
        }
    }
    public function setupPsvTestDataFromContent( $psvContent )
    {
        $psvParser = new PsvParser();
    	$content= $psvParser->parsePsvTree( $psvContent );
        $this->testDbAcleDataInserter->ExecuteMultipleCsv($content);
        return;
        
        $this->databaseTester = NULL;

        $content = $this->_createPsvDataSetFromContent( $psvContent );
        
        
        //$this->parkTablesToMemory();
        $this->getDatabaseTester()->setSetUpOperation($this->getSetUpOperation());
        $this->getDatabaseTester()->setDataSet( $content );
        $this->getDatabaseTester()->onSetUp();
    }

    /**
     * Returns the test database connection.
     *
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        return $this->createDefaultDBConnection( $this->pdo, 'unit_tests');
    }

    /**
     * Gets the IDatabaseTester for this testCase. If the IDatabaseTester is
     * not set yet, this method calls newDatabaseTester() to obtain a new
     * instance.
     *
     * @return PHPUnit_Extensions_Database_ITester
    */
    protected function getDatabaseTester()
    {
        if (empty($this->databaseTester)) {
            $this->databaseTester = $this->newDatabaseTester();
        }

        return $this->databaseTester;
    }

    /**
     * Returns the database operation executed in test setup.
     *
     * @return PHPUnit_Extensions_Database_Operation_DatabaseOperation
    */
    protected function getSetUpOperation()
    {
        return \PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT();
    }

    /**
     * Returns the database operation executed in test cleanup.
     *
     * @return PHPUnit_Extensions_Database_Operation_DatabaseOperation
     */
    protected function getTearDownOperation()
    {
        return PHPUnit_Extensions_Database_Operation_Factory::NONE();
    }

    /**
     * Creates a IDatabaseTester for this testCase.
     *
     * @return PHPUnit_Extensions_Database_ITester
     */
    protected function newDatabaseTester()
    {
        return new \PHPUnit_Extensions_Database_DefaultTester($this->getConnection());
    }

    /**
     * Creates a new DefaultDatabaseConnection using the given PDO connection
     * and database schema name.
     *
     * @param PDO $connection
     * @param string $schema
     * @return PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
     */
    protected function createDefaultDBConnection(\PDO $connection, $schema = '')
    {
        return new \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($connection, $schema);
    }

    /**
     * Returns an operation factory instance that can be used to instantiate
     * new operations.
     *
     * @return PHPUnit_Extensions_Database_Operation_Factory
     */
    protected function getOperations()
    {
        return new \PHPUnit_Extensions_Database_Operation_Factory();
    }

    /**
     *
     * @param string $psv
     * @return \UnitTest\Lib\PsvDataSet
     */
    private function _createPsvDataSetFromContent( $psv )
    {
        return new PsvDataSet( $psv );
    }

    private function _createPsvDataSetFromFile( $psvFile )
    {
        return $this->_createPsvDataSetFromContent( file_get_contents( $psvFile ) );
    }
}
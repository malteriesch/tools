<?php
namespace UnitTest\Lib;

class TestDatabaseSetupHelper 
{
    protected $pdo;
    protected $serviceLocator;
    
    public function __construct($serviceLocator) 
    {
        $this->pdo = $this->createPdoWhilstImportingConstants();
        $this->serviceLocator = $serviceLocator;
    }
    
    protected function createPdoWhilstImportingConstants()
    {
        include_once(APPLICATION_PATH.'/tests/config.php');
        $pdo = new \PDO( 'mysql:dbname='.TEST_DB_NAME.';host='.TEST_DB_SERVER, TEST_DB_USERNAME, TEST_DB_PASSWORD );
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->query("SET FOREIGN_KEY_CHECKS = 0");
        return $pdo;
    }
    
    public function runPatches()
    {
        $databasePatcher = new \Ifp\Utils\Patches\DatabasePatcher($this->serviceLocator, APPLICATION_PATH.'/patches');
        $databasePatcher->setEchoMessages(true);
        $databasePatcher->init();
        $databasePatcher->run();
    }

    public function rebuildTestDatabase(){
        $tableColumn = 'Tables_in_'.TEST_DB_NAME;
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        try{
            foreach ($this->pdo->query("SHOW FULL TABLES  WHERE TABLE_TYPE LIKE 'BASE TABLE' ")->fetchAll() as $table ){
                $this->pdo->query("DROP TABLE {$table[$tableColumn]};");
            }
            $maxSql = file_get_contents("tests/files/max_structure.sql");
            $this->pdo->query($maxSql);
            echo "\nBuilt MAX tables.\n";
            $this->runPatches();

        } catch (\Exception $e){
            echo "\nError:".$e->getMessage();
        }
    }
    
}
<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace UnitTest\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use \UnitTest\Lib\TestDiscovery as TestDiscovery;

class IndexController extends AbstractActionController 
{
    function getTestBase()
    {
        return '/var/www/dev/test-db-acle/tests';
    }
    public function indexAction()
    {
        $unitTestDiscovery = new TestDiscovery();
        $unitTestDiscovery->addFolder($this->getTestBase());
        $viewModel = new ViewModel();
        $viewModel->setVariable('testFiles', $unitTestDiscovery->GetTestFiles());
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    protected function getTestResults($test, $filter = null)
    {
        $phpUnitXml = $this->getTestBase().'/phpunit.xml';
        $tmp = tempnam(sys_get_temp_dir(), "phpunit");
        if($filter){
            $command = "phpunit --log-json $tmp -c $phpUnitXml --filter=$filter $test";
        }else{
            $command = "phpunit --log-json $tmp -c $phpUnitXml $test";
        }
        exec($command);
        $json = str_replace("}{",'},{',"[".file_get_contents($tmp)."]");
        unlink($tmp);
        return json_decode($json,true);
    }
   
    public function standaloneAction()
    {
        $test = $this->params()->fromQuery('test');
        

        $viewModel = new ViewModel();
        $viewModel->setVariable('test', $test);
        $viewModel->setVariable('filters', $this->getClassMethodsFromFile($test));
        $viewModel->setVariable('allGetParameters',(array) $this->params()->fromQuery());
        $viewModel->setVariable('filter', $this->params()->fromQuery('filter'));
        $viewModel->setVariable('testResults', $this->getTestResults($test, $this->params()->fromQuery('filter')));
        return $viewModel;
    }

    protected function assembleXmlMessage($result)
    {

        $failures = [];
        foreach ($result->failures() as $failure) {
            $failures[] = $failure->exceptionMessage();
        }

        $errorCount = $result->failureCount() + $result->errorCount();
        $allCount = $result->count();
        $passCount = $allCount - $errorCount;

        $xmlMessage = "<test>";

        if ($errorCount == 0) {
            $xmlMessage.= "<state>OK</state>";
        } else {
            $xmlMessage.= "<state>FAILED</state>";
        }

        $xmlMessage.="<test_case_progress>$allCount</test_case_progress>";
        $xmlMessage.="<test_case_count>$allCount</test_case_count>";
        $xmlMessage.="<test_case_passes>$passCount</test_case_passes>";
        $xmlMessage.="<test_case_failures>$errorCount</test_case_failures>";
        $xmlMessage.="<test_case_exceptions></test_case_exceptions>";
        $xmlMessage.="<test_case_all_failures>$errorCount</test_case_all_failures>";

        $xmlMessage.="<failures>" . implode("<br/>", $failures) . "</failures>";

        $xmlMessage.="</test>";
        return $xmlMessage;
    }

    public function ajaxAction()
    {
        echo $this->assembleXmlMessage($this->getTestResults($this->params()->fromQuery('test')));
        exit();
    }

    protected function getClassFromFile($testFile)
    {
        return str_replace(".php", '', basename($testFile));
    }

    protected function getClassMethodsFromFile($testFile)
    {
        $file = file_get_contents($testFile);
        preg_match_all('/function\s*(test.*)\s*\(/', $file, $matches);
        return $matches[1];
    }


}

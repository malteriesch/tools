<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace SqlHelper\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{

    function __construct()
    {
        
    }

    public function indexAction()
    {
        $form = new \Zend\Form\Form();
        $form->setAttribute('method', 'post');


        $factory = $this->getServiceLocator()->get('DatabaseFactory');

        $form->add(array(
            'type'    => 'Zend\Form\Element\Select',
            'name'    => 'database',
            'options' => array(
                'label'         => 'Which is your mother tongue?',
                'value_options' => $factory->getDatabases(),
            )
        ));

        $form->add(array(
            'name'       => 'sql',
            'attributes' => array(
                'type'  => 'textarea',
                'class' => 'input-lg',
                'style' => 'width:900px;height:300px;'
        )));

        $form->add(array(
            'name'       => 'replacements',
            'attributes' => array(
                'type'  => 'textarea',
                'class' => 'input-lg',
                'style' => 'width:900px;height:150px;'
        )));

        $form->add(array(
            'name'       => 'exclude',
            'attributes' => array(
                'type'  => 'text',
                'class' => 'input-lg',
                'style' => 'width:900px;'
            ),
        ));
        $form->bind(new \ArrayObject($this->params()->fromPost()));


        $viewModel = new ViewModel();
        $viewModel->setVariable('form', $form);


        $sql = $this->params()->fromPost('sql');
        if ($sql) {
            $db      = $factory->createService($this->params()->fromPost('database'));
            $results = $db->query($sql, [])->toArray();
            $exclude = [];
            if ($this->params()->fromPost('exclude')) {
                $exclude = $this->params()->fromPost('exclude') ? explode(",", $this->params()->fromPost('exclude')) : [];
            }

            preg_match("/FROM\s+([a-zA-z]+)/i",$sql,$matches);
            $table = isset($matches[1]) ? $matches[1] : 'UNKNOWN';

            if ($this->params()->fromPost('replacements')) {
                $yamlParser   = new \Symfony\Component\Yaml\Parser();
                $replacements = $yamlParser->parse($this->params()->fromPost('replacements'));
                $results      = $this->replacePlaceHolders($results, $replacements);
            }
            
            if (empty($results)){
                $formatted = implode("  |", array_column( $db->query("describe  $table", [])->toArray(), 'Field'));
            }else{
                $formatted = \UnitTest\Lib\TestDbAcle\CsvFormatter::format($results, $exclude);
            }

            

            $viewModel->setVariable("result", "[$table]\n$formatted");
        }

        return $viewModel;
    }

    public function formatPsvAction()
    {
        $form = new \Zend\Form\Form();
        $form->setAttribute('method', 'post');


        $factory = $this->getServiceLocator()->get('DatabaseFactory');


        $form->add(array(
            'name'       => 'psv',
            'attributes' => array(
                'type'  => 'textarea',
                'class' => 'input-lg',
                'style' => 'width:900px;height:300px;'
        )));

        $form->add(array(
            'name'       => 'replacements',
            'attributes' => array(
                'type'  => 'textarea',
                'class' => 'input-lg',
                'style' => 'width:900px;height:150px;'
        )));

        $form->add(array(
            'name'       => 'exclude',
            'attributes' => array(
                'type'  => 'text',
                'class' => 'input-lg',
                'style' => 'width:900px;'
            ),
        ));
        $form->bind(new \ArrayObject($this->params()->fromPost()));


        $viewModel = new ViewModel();
        $viewModel->setVariable('form', $form);


        $psv = $this->params()->fromPost('psv');
        if ($psv) {
//            $parser = new \UnitTest\Lib\TestDbAcle
//            $results = ;
            
            $exclude = [];
            if ($this->params()->fromPost('exclude')) {
                $exclude = $this->params()->fromPost('exclude') ? explode(",", $this->params()->fromPost('exclude')) : [];
            }


            if ($this->params()->fromPost('replacements')) {
                $yamlParser   = new \Symfony\Component\Yaml\Parser();
                $replacements = $yamlParser->parse($this->params()->fromPost('replacements'));
                $results      = $this->replacePlaceHolders($replacements, $replacements);
            }

            $formatted = \UnitTest\Lib\TestDbAcle\CsvFormatter::format($results, $exclude);

            $viewModel->setVariable("result", $formatted);
        }

        return $viewModel;
    }

    protected function replacePlaceHolders($results, $replacements)
    {
        $filteredResults = [];
        foreach ($results as $row) {
            foreach ($row as $header => $value) {
                if (isset($replacements[$header][$value])) {
                    $row[$header] = $replacements[$header][$value];
                }
            }
            $filteredResults[] = $row;
        }
        return $filteredResults;
    }

    public function sqlToBuilderAction()
    {
        $form = new \Zend\Form\Form();
        $form->setAttribute('method', 'post');


        $form->add(array(
            'name'       => 'sql',
            'attributes' => array(
                'type'  => 'textarea',
                'class' => 'input-lg',
                'style' => 'width:900px;height:300px;'
            ),
        ));
        $form->add(array(
            'name'       => 'variable',
            'attributes' => array(
                'type' => 'text',
            ),
        ));
        $form->bind(new \ArrayObject($this->params()->fromPost()));


        $viewModel = new ViewModel();
        $viewModel->setVariable('form', $form);


        $sql          = $this->params()->fromPost('sql');
        $variableName = $this->params()->fromPost('variable');
        if ($sql) {
            $parser    = new \PHPSQL\Parser();
            $parsedSql = $parser->parse($sql); //var_dump($parsedSql);
            $result    = [];

            $result[] = "{$variableName}->from(\"{$parsedSql['FROM'][0]['base_expr']}\";";

            foreach ($parsedSql['SELECT'] as $index => $parsedSelect) {
                if ($parsedSelect['alias']) {
                    $column = $parsedSelect['alias']['name'] . ' ' . $parsedSelect['alias']['base_expr'];
                } else {
                    $column = $parsedSelect['base_expr'];
                }
                if (trim($column)) {
                    $result[] = "{$variableName}->addColumn(\"$column\")";
                }
            }

            foreach ($parsedSql['FROM'] as $index => $parsedFrom) {
                if ($index == 0) {
                    continue;
                } else {
                    list($table, $condition) = preg_split("/ ON /i", $parsedFrom['base_expr']);
                    if ($parsedFrom['join_type'] == 'JOIN') {
                        $result[] = "{$variableName}->innerJoin(\"$table\",\"$condition\")";
                    }
                    if ($parsedFrom['join_type'] == 'LEFT') {
                        $result[] = "{$variableName}->leftJoin(\"$table\",\"$condition\")";
                    }
                }
            }

            $viewModel->setVariable("result", implode("\n", $result));
        }

        return $viewModel;
    }

    public function analyzeClassAction()
    {
        $form = new \Zend\Form\Form();
        $form->setAttribute('method', 'post');


        $form->add(array(
            'name'       => 'class',
            'attributes' => array(
                'type'  => 'text',
                'class' => 'input-lg',
            ),
        ));
        $form->add(array(
            'name'       => 'code',
            'attributes' => array(
                'type'  => 'textarea',
                'class' => 'input-lg',
                'style' => 'width:900px;height:300px;'
            ),
        ));
        $form->bind(new \ArrayObject($this->params()->fromPost()));


        $viewModel = new ViewModel();
        $viewModel->setVariable('form', $form);

        $code           = $this->params()->fromPost('code');
        eval($code);
        $reflectedClass = new \ReflectionClass($this->params()->fromPost('class'));
        var_dump($reflectedClass->getMethods());
        return $viewModel;
    }

}

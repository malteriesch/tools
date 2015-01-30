<?php

return array(
    'router' => array(
        'routes' => array(
            'db-to-psv' => array(
                'type'    => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/db-to-psv',
                    'defaults' => array(
                        'controller' => 'SqlHelper\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
            'format-psv' => array(
                'type'    => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/format-psv',
                    'defaults' => array(
                        'controller' => 'SqlHelper\Controller\Index',
                        'action'     => 'formatPsv',
                    ),
                ),
            ),
            'sql-to-builder' => array(
                'type'    => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/sql-to-builder',
                    'defaults' => array(
                        'controller' => 'SqlHelper\Controller\Index',
                        'action'     => 'sqlToBuilder',
                    ),
                ),
            ),
            'analyze-class' => array(
                'type'    => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/analyze-class',
                    'defaults' => array(
                        'controller' => 'SqlHelper\Controller\Index',
                        'action'     => 'analyzeClass',
                    ),
                ),
            ),
        ),
    ),
    'asset_manager' => array(
        'resolver_configs' => array(
            'paths' => array(
                __DIR__ . '/../public',
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);


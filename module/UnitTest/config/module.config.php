<?php
return array(
        'controllers' => array(
                'invokables' => array(
                        'UnitTest\Controller\Index'       => 'UnitTest\Controller\IndexController',

                ),
        ),
        'view_manager' => array(
                'template_path_stack' => array(
                        'unit-test' => __DIR__ . '/../view',
                ),
        ),
        'router' => array(
                'routes' => array(
                        'unittest' => array(
                                'type'    => 'segment',
                                'options' => array(
                                        'route'    => '/unittest[/:action]',
                                        'constraints' => array(
                                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        ),
                                        'defaults' => array(
                                                'controller' => 'UnitTest\Controller\Index',
                                                'action'     => 'index',
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
);
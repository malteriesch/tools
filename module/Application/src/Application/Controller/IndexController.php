<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }

    public function unserialiseAction()
    {
        $form               = new \Zend\Form\Form();
        $form->setAttribute('method', 'post');

         $form->add(array(
            'name' => 'code',
            'attributes' => array(
                'type'  => 'textarea',
                'class' => 'input-lg',
                'style'=>'width:900px;height:300px;'
            ),
        ));
        $form->bind(new \ArrayObject($this->params()->fromPost()));


        $viewModel          = new ViewModel();
        $viewModel->setVariable('form', $form);

        $code = $this->params()->fromPost('code');
        if ($code){
            $viewModel->setVariable("result", \DevelopmentLib\ArrayExporter::exportPhp54(unserialize($code)));
        }
        return $viewModel;
    }
}

<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Symfony\Component\Filesystem\Filesystem;

class AdminUploadController extends Controller
{
	public function uploadAction(Request $request){
		 $form = $this->createFormBuilder()
		 		->add('choiceFile', FileType::class)
                ->add('choiceType',ChoiceType::class,
                    array('choices' => array(
                        '1' => 'BrochureEte.pdf',
                        '2' => 'BrochureHiver.pdf'),
                    'multiple'=>false,'expanded'=>true))
				->add('save', SubmitType::class, array('label' => 'Uploader'))
                ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted()){
        	$data = $form->getData();
        	$data['choiceFile']->move($this->getParameter('brochures_directory'),$data['choiceType']);
            return $this->render('AppBundle:Admin:upload.html.twig', array(
            'form' => $form->createView(),
            'ok' => 'ok'));
        }
		return $this->render('AppBundle:Admin:upload.html.twig', array(
            'form' => $form->createView()));
	}
}
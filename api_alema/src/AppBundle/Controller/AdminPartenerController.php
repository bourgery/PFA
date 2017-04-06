<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use AppBundle\Entity\Partener;


class AdminPartenerController extends Controller
{
	public function addPartenerAction(Request $request){
		 $form = $this->createFormBuilder()
		 		->add('description', TextareaType::class)
                ->add('urlSite', TextType::class)
                ->add('urlImg', TextType::class)
				->add('save', SubmitType::class, array('label' => 'Ajouter'))
                ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted()){
        	$data = $form->getData();
            if(strlen($data['description']) >= 500){
                return $this->render('AppBundle:Admin:add_partener.html.twig', array(
                'form' => $form->createView(),
                'ko' => 'ok'));
            }
            $partener = new Partener();
            $partener->setUrlSite($data['urlSite']);
            $partener->setUrlImg($data['urlImg']);
            $partener->setDescription($data['description']);
            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($partener);
            $em->flush();
            return $this->render('AppBundle:Admin:add_partener.html.twig', array(
            'form' => $form->createView(),
            'ok' => 'ok'));
        }
		return $this->render('AppBundle:Admin:add_partener.html.twig', array(
            'form' => $form->createView()));
	}
}
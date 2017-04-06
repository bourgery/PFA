<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use AppBundle\Entity\Trip;
use AppBundle\Form\Type\TripType;
use AppBundle\Entity\Picture;
use Symfony\Component\Filesystem\Filesystem;

class AdminUserController extends Controller
{
	public function blockUserAction(Request $request){
		 $form = $this->createFormBuilder()
		 		->add('user', TextType::class)
				->add('save', SubmitType::class, array('label' => 'Bloquer'))
                ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted()){
        	$data = $form->getData();
        	$user = $this->get('doctrine.orm.entity_manager')
                	->getRepository('AppBundle:User')
                	->find($data['user']);
            if(empty($user)){
            	return $this->render('AppBundle:Admin:block_user.html.twig', array(
            'form' => $form->createView(),
            'ko' => 'ko'));
            }
            else{
                $user->setBlocked(true);
                $em = $this->get('doctrine.orm.entity_manager');
                $em->merge($user);
                $em->flush();
                $tokens = $this->get('doctrine.orm.entity_manager')
                          ->getRepository('AppBundle:AuthToken')
                          ->findByUser($user->getLogin());
                foreach($tokens as $token){
                    $em->remove($token);
                }
                $em->flush();
        		return $this->render('AppBundle:Admin:block_user.html.twig', array(
            	'form' => $form->createView(),
            	'ok' => 'ok'));
        	}
        }
		return $this->render('AppBundle:Admin:block_user.html.twig', array(
            'form' => $form->createView()));
	}

    public function unblockUserAction(Request $request){
         $form = $this->createFormBuilder()
                ->add('user', TextType::class)
                ->add('save', SubmitType::class, array('label' => 'Débloquer'))
                ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted()){
            $data = $form->getData();
            $user = $this->get('doctrine.orm.entity_manager')
                    ->getRepository('AppBundle:User')
                    ->find($data['user']);
            if(empty($user)){
                return $this->render('AppBundle:Admin:unblock_user.html.twig', array(
            'form' => $form->createView(),
            'ko' => 'ko'));
            }
            else{
                $user->setBlocked(false);
                $em = $this->get('doctrine.orm.entity_manager');
                $em->merge($user);
                $em->flush();
                return $this->render('AppBundle:Admin:unblock_user.html.twig', array(
                'form' => $form->createView(),
                'ok' => 'ok'));
            }
        }
        return $this->render('AppBundle:Admin:unblock_user.html.twig', array(
            'form' => $form->createView()));
    }
}
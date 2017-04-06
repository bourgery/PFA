<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use AppBundle\Entity\Trip;
use AppBundle\Form\Type\TripType;
use AppBundle\Entity\Picture;
use Symfony\Component\Filesystem\Filesystem;

class AdminTripController extends Controller
{
	public function addTripAction(Request $request){
		$trip = new Trip();
    	$form = $this->createForm(TripType::class, $trip);
    	$form->handleRequest($request);
        if($form->isSubmitted()){
            $data = $form->getData();
            if(strlen($data->getDescription()) >= 1000){
               return $this->render('AppBundle:Admin:add_trip.html.twig', array(
                    'form' => $form->createView(),
                    'ko' => "La description doit être inférieur à 1000 caractères")); 
            }
            if($data->getDateStart() > $data->getDateEnd()){
               return $this->render('AppBundle:Admin:add_trip.html.twig', array(
                    'form' => $form->createView(),
                    'ko' => "La date de départ doit être inférieur à la date de fin"));  
            }
        	if($form->isValid()){
        		$em = $this->get('doctrine.orm.entity_manager');
    			$em->persist($trip);
    			$em->flush();
        		return $this->render('AppBundle:Admin:add_trip.html.twig', array(
            		'form' => $form->createView(),
            		'ok' => "ok"));
        	}
        	else{
        		return $this->render('AppBundle:Admin:add_trip.html.twig', array(
            		'form' => $form->createView(),
            		'ko' => "Le nombre de place doit être un entier"));
        	}
        }
		return $this->render('AppBundle:Admin:add_trip.html.twig', array(
            'form' => $form->createView()));
	}

    public function deleteActualityAction(Request $request){
        return $this->choiceTripAction($request, "delete_actuality_execute", "Supprimer une actualité");
    }

    public function deleteActualityExecuteAction($id, Request $request){
        $form = $this->createFormBuilder();
        $qb = $this->get('doctrine.orm.entity_manager')->createQueryBuilder();
        $qb->select('a')
           ->from('AppBundle:Actuality', 'a')
           ->orderBy('a.date', 'DESC')
           ->where('a.idTrip = :idTrip')
           ->setParameter('idTrip', $id);
        $actualities = $qb->getQuery()->getResult();
        $i = 0;
        foreach ($actualities as $actuality) {
            $tab[$i] = $actuality->getId();
            $i ++;
            $form->add($actuality->getId(), CheckboxType::class, array(
                'value' => $actuality->getId(),
                'required' => false));
            $actuality->setPictureName('../../uploads/actualities/'.$actuality->getPictureName());
        }
        $form->add('deleteAction', SubmitType::class, array('label' => 'Supprimer'));
        $form = $form->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted()){
            $data = $form->getData();
            foreach ($tab as $id) {
                if($data[$id] == true){
                    $actuality = $this->get('doctrine.orm.entity_manager')
                        ->getRepository('AppBundle:Actuality')
                        ->find($id);
                    $fileName = $this->getParameter('actualities_directory').'/'.$actuality->getPictureName();
                    $fs = new Filesystem();
                    if($fs->exists($fileName)){
                        $fs->remove($fileName);
                    }
                    $em = $this->get('doctrine.orm.entity_manager');
                    $likeActuality = $actuality->getLikeActuality();
                    foreach ($likeActuality as $like) {
                        $em->remove($like);
                    }
                    $em->remove($actuality);
                    $em->flush();
                }
            }
            return $this->redirectToRoute('delete_actuality');
        }
        return $this->render('AppBundle:Admin:delete_actuality_execute.html.twig', array(
                   'form' => $form->createView(),
                   'actualities' => $actualities));
    }

	public function getPictureAction(Request $request){
		$trips = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:Trip')
                ->findAll();
        $tabTrip = $this->transformTabTrip($trips);
        $form = $this->createFormBuilder()
                ->add('choiceTrip', ChoiceType::class, [
                    'choices' => $tabTrip])
				->add('save', SubmitType::class, array('label' => 'Valider'))
                ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted()){
        	$data = $form->getData();
        	$qb = $this->get('doctrine.orm.entity_manager')->createQueryBuilder();
        	$qb->select('p')
               ->from('AppBundle:Picture', 'p')
           	   ->orderBy('p.date', 'DESC')
               ->where('p.idTrip = :idTrip')
               ->setParameter('idTrip', $data["choiceTrip"]);
        	$pictures = $qb->getQuery()->getResult();
        	foreach ($pictures as $picture) {
        		$picture->setPictureName('../uploads/pictures/'.$picture->getPictureName());
        	}
        	return $this->render('AppBundle:Admin:get_picture.html.twig', array(
            	   'form' => $form->createView(),
            	   'pictures' => $pictures));
        }
		return $this->render('AppBundle:Admin:get_picture.html.twig', array(
            'form' => $form->createView()));
	}

    public function deletePictureAction(Request $request){
        return $this->choiceTripAction($request, "delete_picture_execute", "Supprimer une photo");
    }

	public function deletePictureExecuteAction($id, Request $request){
		$form1 = $this->createFormBuilder();
		$qb = $this->get('doctrine.orm.entity_manager')->createQueryBuilder();
    	$qb->select('p')
           ->from('AppBundle:Picture', 'p')
       	   ->orderBy('p.date', 'DESC')
           ->where('p.idTrip = :idTrip')
           ->setParameter('idTrip', $id);
    	$pictures = $qb->getQuery()->getResult();
    	$i = 0;
    	foreach ($pictures as $picture) {
    		$tab[$i] = $picture->getId();
    		$i ++;
    		$form1->add($picture->getId(), CheckboxType::class, array(
    			'value' => $picture->getId(),
    			'required' => false));
    		$picture->setPictureName('../../uploads/pictures/'.$picture->getPictureName());
    	}
    	$form1->add('deleteAction', SubmitType::class, array('label' => 'Supprimer'));
    	$form1 = $form1->getForm();
    	$form1->handleRequest($request);
    	if($form1->isSubmitted()){
    		$data = $form1->getData();
    		foreach ($tab as $id) {
    			if($data[$id] == true){
    				$picture = $this->get('doctrine.orm.entity_manager')
			            ->getRepository('AppBundle:Picture')
			            ->find($id);
					$fileName = $this->getParameter('pictures_directory').'/'.$picture->getPictureName();
			    	$fs = new Filesystem();
			    	if($fs->exists($fileName)){
			        	$fs->unlink($fileName);
			    	}
					$em = $this->get('doctrine.orm.entity_manager');
			    	$likePicture = $picture->getLikePicture();
			    	foreach ($likePicture as $like) {
			        	$em->remove($like);
			    	}
			    	$em->remove($picture);
			    	$em->flush();
    			}
    		}
    		return $this->redirectToRoute('delete_picture');
    	}
    	return $this->render('AppBundle:Admin:delete_picture_execute.html.twig', array(
            	   'form1' => $form1->createView(),
            	   'pictures' => $pictures));
	}

    public function deleteCommentAction(Request $request){
        return $this->choiceTripAction($request, "delete_comment_execute", "Supprimer un commentaire");
    }

    public function deleteCommentExecuteAction(Request $request, $id){
        $qb = $this->get('doctrine.orm.entity_manager')->createQueryBuilder();
        $qb->select('c')
           ->from('AppBundle:Comment', 'c')
           ->orderBy('c.date', 'DESC')
           ->where('c.idTrip = :idTrip')
           ->setParameter('idTrip', $id);
        $comments = $qb->getQuery()->getResult();
        $form = $this->createFormBuilder();
        $i = 0;
        foreach ($comments as $comment) {
            $tab[$i] = $comment->getId();
            $i ++;
            $form->add($comment->getId(), CheckboxType::class, array(
                'value' => $comment->getId(),
                'required' => false));
        }
        $form->add('deleteAction', SubmitType::class, array('label' => 'Supprimer'));
        $form = $form->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted()){
            $data = $form->getData();
            foreach ($tab as $id) {
                if($data[$id] == true){
                    $comment = $this->get('doctrine.orm.entity_manager')
                        ->getRepository('AppBundle:Comment')
                        ->find($id);
                    $em = $this->get('doctrine.orm.entity_manager');
                    $em->remove($comment);
                    $em->flush();
                }   
            }
            return $this->redirectToRoute('delete_comment');
        }
        
        return $this->render('AppBundle:Admin:delete_comment_execute.html.twig', array(
                   'form' => $form->createView(),
                   'comments' => $comments));
    }

    public function deleteTripAction(Request $request){
        $trips = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:Trip')
                ->findAll();
        $tabTrip = $this->transformTabTrip($trips);
        $form = $this->createFormBuilder()
                ->add('choiceTrip', ChoiceType::class, [
                    'choices' => $tabTrip])
                ->add('save', SubmitType::class, array('label' => 'Supprimer'))
                ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted()){
            $data = $form->getData();
            $idTrip = $data['choiceTrip'];
            $trip = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:Trip')
                ->find($idTrip);
            $this->deletePicture($idTrip);
            $this->deleteComment($idTrip);
            $this->deleteParticipateTrip($idTrip);
            $this->deleteActuality($idTrip);
            $em = $this->get('doctrine.orm.entity_manager');
            $em->remove($trip);
            $em->flush();
            return $this->render('AppBundle:Admin:delete_trip.html.twig', array(
                   'form' => $form->createView(),
                   'ok' => 'ok'));
        }
        return $this->render('AppBundle:Admin:delete_trip.html.twig', array(
                   'form' => $form->createView()));
    }

    private function choiceTripAction(Request $request, $view, $title){
        $trips = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:Trip')
                ->findAll();
        $tabTrip = $this->transformTabTrip($trips);
        $form = $this->createFormBuilder()
                ->add('choiceTrip', ChoiceType::class, [
                    'choices' => $tabTrip])
                ->add('save', SubmitType::class, array('label' => 'Valider'))
                ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted()){
            $data = $form->getData();
            return $this->redirectToRoute($view, array("id" => $data["choiceTrip"]));
        }
        return $this->render('AppBundle:Admin:choice_trip.html.twig', array(
                   'form' => $form->createView(),
                   'title' => $title));
    }
    private function deleteParticipateTrip($idTrip){
        $qb = $this->get('doctrine.orm.entity_manager')->createQueryBuilder();
        $qb->select('p')
           ->from('AppBundle:ParticipateTrip', 'p')
           ->where('p.idTrip = :idTrip')
           ->setParameter('idTrip', $idTrip);
        $participateTrips = $qb->getQuery()->getResult();
        foreach ($participateTrips as $participateTrip) {
            $em = $this->get('doctrine.orm.entity_manager');
            $em->remove($participateTrip);
            $em->flush();
        }
    }

    private function deleteComment($idTrip){
        $qb = $this->get('doctrine.orm.entity_manager')->createQueryBuilder();
        $qb->select('c')
           ->from('AppBundle:Comment', 'c')
           ->orderBy('c.date', 'DESC')
           ->where('c.idTrip = :idTrip')
           ->setParameter('idTrip', $idTrip);
        $comments = $qb->getQuery()->getResult();
        foreach ($comments as $comment) {
            $em = $this->get('doctrine.orm.entity_manager');
            $em->remove($comment);
            $em->flush();
        }
    }

    private function deleteActuality($idTrip){
        $qb = $this->get('doctrine.orm.entity_manager')->createQueryBuilder();
        $qb->select('a')
           ->from('AppBundle:Actuality', 'a')
           ->orderBy('a.date', 'DESC')
           ->where('a.idTrip = :idTrip')
           ->setParameter('idTrip', $idTrip);
        $actualities = $qb->getQuery()->getResult();
        foreach ($actualities as $actuality) {
            $em = $this->get('doctrine.orm.entity_manager');
            $likeActuality = $actuality->getLikeActuality();
            foreach ($likeActuality as $like) {
                $em->remove($like);
            }
            $em->remove($actuality);
            $em->flush();
        }
    }

    private function deletePicture($idTrip){
        $qb = $this->get('doctrine.orm.entity_manager')->createQueryBuilder();
        $qb->select('p')
           ->from('AppBundle:Picture', 'p')
           ->orderBy('p.date', 'DESC')
           ->where('p.idTrip = :idTrip')
           ->setParameter('idTrip', $idTrip);
        $pictures = $qb->getQuery()->getResult();
        foreach ($pictures as $picture) {
            $em = $this->get('doctrine.orm.entity_manager');
            $likePicture = $picture->getLikePicture();
            foreach ($likePicture as $like) {
                $em->remove($like);
            }
            $em->remove($picture);
            $em->flush();
        }
    }

	private function transformTabTrip($trips){
        foreach ($trips as $trip) {
            $tab[$trip->getName()] = $trip->getId();
        }
        return $tab; 
    }
}
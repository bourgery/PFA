<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use AppBundle\Entity\Trip;
use AppBundle\Entity\Child;
use AppBundle\Entity\ParticipateTrip;
use AppBundle\Entity\AccessChild;

class AdminChildController extends Controller
{
	public function importAction(Request $request){
        $trips = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:Trip')
                ->findAll();
        $tabTrip = $this->transformTabTrip($trips);
        $form = $this->createFormBuilder()
                ->add('choiceTrip', ChoiceType::class, [
                    'choices' => $tabTrip])
                ->add('choiceFile', FileType::class)
                ->add('save', SubmitType::class, array('label' => 'Valider'))
                ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted()){
            $data = $form->getData();
            $excel = $this->get('os.excel');
            $excel->loadFile($data['choiceFile']->getRealPath());
            $nbRow = $excel->getRowCount() - 2;
            $nbColumn = $excel->getColumnCount();
            for($i = 4; $i <= $nbRow; $i++){
                $this->addChildTrip($excel->getCellData($i, 1), $excel->getCellData($i, 2), $excel->getCellData($i, 3), $excel->getCellData($i, 4), $data['choiceTrip']);
            }
            return $this->render('AppBundle:Admin:import.html.twig', array(
                   'form' => $form->createView(),
                   'ok' => 'ok'));
        }
        return $this->render('AppBundle:Admin:import.html.twig', array(
                   'form' => $form->createView()));
    }

    private function addChild($lastname, $firstname, $birthDate, $familyNumber){
        $child = new Child();
        $child->setFirstname($firstname);
        $child->setLastname($lastname);
        $child->setBirthDate($birthDate);
        $child->setFamilyNumber($familyNumber);
        $em = $this->get('doctrine.orm.entity_manager');
        $relative =  $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:Relative')
                ->findOneByFamilyNumber($child->getFamilyNumber());
        if(!empty($relative)){
            $child->setRelative($relative);
            $accessChild = new AccessChild();
            $accessChild->setLoginUser($relative->getUser());
            $accessChild->setIdChild($child);
            $accessChild->setFamilyLink("Responsable");
            $em->persist($accessChild);
        }

        $em->persist($child);
        $em->flush();
        return $child;
    }

    private function addChildTrip($lastname, $firstname, $birthDate, $familyNumber, $idTrip){
        $date = new \DateTime($this->changeDisplayDate($birthDate));
        $child = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:Child')
                ->findOneBy(array('lastname' => $lastname, 'firstname' => $firstname, 'birthDate' => $date));
        if(empty($child)){
            $child = $this->addChild($lastname, $firstname, $date, $familyNumber);
        }
        $trip =  $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:Trip')
                ->find($idTrip);
        $participateTripBis = $this->get('doctrine.orm.entity_manager')
                             ->getRepository('AppBundle:ParticipateTrip')
                             ->findOneBy(array('idTrip' => $idTrip, 'idChild' => $child->getId()));
        if(empty($participateTripBis)){
            $participateTrip = new ParticipateTrip();
            $participateTrip->setIdTrip($trip);
            $participateTrip->setIdChild($child);
            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($participateTrip);
            $em->flush();
        }
    }

	private function transformTabTrip($trips){
        foreach ($trips as $trip) {
            $tab[$trip->getName()] = $trip->getId();
        }
        return $tab; 
    }

    private function changeDisplayDate($date){
        $newDate = substr($date, 6, 4)."-".substr($date, 3, 2)."-".substr($date, 0, 2);
        return $newDate;
    }
}
<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations\QueryParam;

class BrochureController extends Controller
{
	/**
     * @Rest\View(statusCode=Response::HTTP_CREATED)
     * @Rest\POST("/brochure-summer")
     */
	public function postBrochureSummeraction(Request $request){
		$message = \Swift_Message::newInstance();
        $message->setFrom('no-replay@yoannbourgery.com');
        $message->setSubject("Alema : Envoie de la brochure d'été");        
        if(!empty($request->get('email'))){
            $message->setTo($request->get('email'));
            $message->setBody('Bonjour veuillez trouver le lien de téléchargement de la brochure d\'été <a href="https://alema.yoannbourgery.com/uploads/brochures/BrochureEte.pdf">lien</a>','text/html');
            //$message->attach(\Swift_Attachment::fromPath($this->getParameter('brochures_directory')."BrochureEte.pdf"));
        }
        else{
            $message->setTo("y.bourgery@gmail.com");
            $message->setBody('Envoie de brochure d\'été à'.' '.$request->get('lastname').' '.$request->get('firstname').' à l\'adresse '.$request->get('address').' '.$request->get('postcode').' '.$request->get('city'));
        }
        $this->get('mailer')->send($message);
	}

    /**
     * @Rest\View(statusCode=Response::HTTP_CREATED)
     * @Rest\POST("/brochure-winter")
     */
    public function postBrochureWinteraction(Request $request){
        $message = \Swift_Message::newInstance();
        $message->setFrom('no-replay@yoannbourgery.com');
        $message->setSubject("Alema : Envoie de la brochure d'hiver");        
        if(!empty($request->get('email'))){
            $message->setTo($request->get('email'));
            $message->setBody('Bonjour veuillez trouver le lien de téléchargement de la brochure d\'hiver <a href="https://alema.yoannbourgery.com/uploads/brochures/BrochureHiver.pdf">lien</a>','text/html');
            //$message->attach(\Swift_Attachment::fromPath($this->getParameter('brochures_directory')."BrochureEte.pdf"));
        }
        else{
            $message->setTo("y.bourgery@gmail.com");
            $message->setBody('Envoie de brochure d\'hiver à'.' '.$request->get('lastname').' '.$request->get('firstname').' à l\'adresse '.$request->get('address').' '.$request->get('postcode').' '.$request->get('city'));
        }
        $this->get('mailer')->send($message);
    }
}
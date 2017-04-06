<?php
namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Partener;

class PartenerController extends Controller
{
	/**
    * @Rest\View()
    * @Rest\GET("/parteners")
    */
    public function getLostUsersAction(Request $request)
    {   
        $parteners = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:Partener')
                ->findAll();
        return $parteners;
    }
}
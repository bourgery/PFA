<?php
namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use AppBundle\Entity\Comment;

class CommentController extends Controller
{
	/**
     * @Rest\View(serializerGroups={"comment"})
     * @Rest\Get("/trips/{idTrip}/comments")
     */
    public function getCommentsAction(Request $request)
    {
        $idTrip = $request->get('idTrip');
        $loginConnect = $this->get('security.token_storage')->getToken()->getUser()->getLogin();
        $qb = $this->get('doctrine.orm.entity_manager')->createQueryBuilder();
        $qb->select('c')
           ->from('AppBundle:Comment', 'c')
           ->orderBy('c.date', 'DESC')
           ->where('c.idTrip = :idTrip')
           ->setParameter('idTrip', $idTrip);
        $trip = $this->get('doctrine.orm.entity_manager')
              ->getRepository('AppBundle:Trip')
              ->find($idTrip);
        $director = $this->container->get('security.authorization_checker')->isGranted('ROLE_DIRECTOR') && $trip->getManager()->getUser()->getLogin() === $loginConnect;
        if(!$director && ($this->userIsPermit($idTrip, $loginConnect) === false)){
            return $this->userNotPermit();
        }
        $comments = $qb->getQuery()->getResult();
        return $comments;
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_NO_CONTENT)
     * @Rest\Delete("/comments/{id}")
     */
    public function removeCommentsAction(Request $request)
    {
        $loginConnect = $this->get('security.token_storage')->getToken()->getUser()->getLogin();
        $comment = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:Comment')
                ->find($request->get('id'));
        if(empty($comment)){
            $this->commentNotFound();
        }
        $commentUser = $loginConnect === $comment->getLoginUser()->getLogin();
        $trip = $this->get('doctrine.orm.entity_manager')
              ->getRepository('AppBundle:Trip')
              ->findOneById($comment->getIdTrip()->getId());
        $director = $this->container->get('security.authorization_checker')->isGranted('ROLE_DIRECTOR') && $trip->getManager()->getUser()->getLogin() === $loginConnect;
        if(!$commentUser && !$director){
            return $this->userNotPermit();
        }
        $em = $this->get('doctrine.orm.entity_manager');
        $em->remove($comment);
        $em->flush();
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_CREATED, serializerGroups={"comment"})
     * @Rest\POST("/trips/{idTrip}/comments")
     */
    public function postCommentAction(Request $request){
        $idTrip = $request->get('idTrip');
        $trip = $this->get('doctrine.orm.entity_manager')
              ->getRepository('AppBundle:Trip')
              ->find($idTrip);
        $loginUser = $this->get('security.token_storage')->getToken()->getUser()->getLogin();
        $director = $this->container->get('security.authorization_checker')->isGranted('ROLE_DIRECTOR') && $trip->getManager()->getUser()->getLogin() === $loginUser;
        if(!$director && $this->userIsPermit($idTrip, $loginUser) === false){
            return $this->userNotPermit();
        }
        $user = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:User')
                ->find($loginUser);
        $trip = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:Trip')
                ->find($idTrip);
        $comment = new Comment();
        $comment->setLoginUser($user);
        $comment->setIdTrip($trip);
        $comment->setText($request->get('text'));
        $em = $this->get('doctrine.orm.entity_manager');
        $em->persist($comment);
        $em->flush();
        return $comment;
    }

    private function userIsPermit($idTrip, $login){
        $user = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:User')
                ->find($login);
        $children = $user->getAccessChildren();
        $trip = new ArrayCollection();
        foreach ($children as $child) {
            $id = $child->getIdChild()->getId();
            $participateTrip = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:ParticipateTrip')
                ->findByIdChild($id);
            if(!empty($participateTrip)){
                foreach ($participateTrip as $trips) {
                    $trip->add($trips->getIdTrip()->getId());
                }
            }
        }
        return $trip->indexOf(intval($idTrip));
    }

    private function userNotPermit(){
        throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Bad user',null, 403);
    }

    private function commentNotFound()
    {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Comment not found');
    }

    private function tripNotFound()
    {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Trip not found');
    }
}
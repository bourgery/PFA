<?php
namespace AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Type\UserType;
use AppBundle\Entity\User;

class UserController extends Controller
{
	/**
    * @Rest\View(statusCode=Response::HTTP_CREATED, serializerGroups={"user"})
    * @Rest\POST("/users/lost/{login}")
    */
    public function getLostUsersAction(Request $request)
    {   
        $user = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:User')
                ->find($request->get('login'));
        /* @var $user User */
        if($request->get('email') != $user->getEmail()){
            return $this->userNotPermit();
        }
        $char = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $mot_de_passe = str_shuffle($char);
        $mot_de_passe = substr($mot_de_passe, 0, 8);
        $encoder = $this->get('security.password_encoder');
        $encoded = $encoder->encodePassword($user, $mot_de_passe);
        $user->setPassword($encoded);
        $message = \Swift_Message::newInstance();
        $message->setSubject("Alema : Perte de mot de passe");
        $message->setFrom('no-reply@yoannbourgery.com');
        $message->setTo($user->getEmail());
        $message->setBody('Bonjour <span style="font-weight: bold;">'.ucfirst($user->getFirstname()).',</span><br/><p>Vous avez demandez une réinitialisation de votre mot de passe. </p><p>Votre nouveau mot de passe est : <span style="font-weight: bold;">'.$mot_de_passe.'</span></p><p>Nous vous conseillons de changer votre mot de passe en vous rendant dans l\'application dans la rubrique mon compte.</p><br/>Merci de ne pas répondre à cet email.<br/><img src=""/>','text/html');
        $this->get('mailer')->send($message);
        $em = $this->get('doctrine.orm.entity_manager');
        $em->merge($user);
        $em->flush();
        return $mot_de_passe;
    }

    /**
     * @Rest\View(serializerGroups={"user"})
     * @Rest\Get("/users/{login}") 
     */
    //Il faut que ça soit le bon compte personne ne peut voir les données des autres
    public function getUserAction(Request $request)
    {
        $userToken = $this->get('security.token_storage')->getToken()->getUser();
        $user = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:User')
                ->find($request->get('login'));
        /* @var $user User */
        if($userToken->getLogin() != $user->getLogin()){
            return $this->userNotPermit();
        }
        if (empty($user)) {
            return $this->userNotFound();
        }
        return $user;
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_CREATED, serializerGroups={"user"})
     * @Rest\Post("/users")
     */
    public function postUsersAction(Request $request)
    {
    	$user = new User();
    	$form = $this->createForm(UserType::class, $user);
    	$form->submit($request->request->all());
    	if($form->isValid()){
            $encoder = $this->get('security.password_encoder');
            // le mot de passe en claire est encodé avant la sauvegarde
            $encoded = $encoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($encoded);
    		$em = $this->get('doctrine.orm.entity_manager');
    		$em->persist($user);
    		$em->flush();
    		return $user;
    	}
    	else{
    		return $form;
    	}
    }

    /**
     * @Rest\View(serializerGroups={"user"})
     * @Rest\Put("/users/{login}")
     */
    public function updateUserAction(Request $request)
    {
        return $this->updateUser($request, true, false);
    }

    /**
     * @Rest\View(serializerGroups={"user"})
     * @Rest\Patch("/users/{login}")
     */
    public function patchUserAction(Request $request)
    {
        return $this->updateUser($request, false, false);
    }

    /**
     * @Rest\View(serializerGroups={"user"})
     * @Rest\Patch("/users/changePassword/{login}")
     */
    public function patchUserPasswordAction(Request $request)
    {
        return $this->updateUser($request, false, true);
    }

    private function updateUser(Request $request, $clearMissing, $password)
    {
        $userToken = $this->get('security.token_storage')->getToken()->getUser();
        $user = $this->get('doctrine.orm.entity_manager')
                ->getRepository('AppBundle:User')
                ->find($request->get('login'));
        /* @var $user User */

        if (empty($user)) {
            return $this->userNotFound();
        }

        if($userToken->getLogin()!= $user->getLogin()){
            return $this->userNotPermit();
        }

        if ($clearMissing) { // Si une mise à jour complète, le mot de passe doit être validé
            $options = ['validation_groups'=>['Default', 'FullUpdate']];
        } else {
            $options = []; // Le groupe de validation par défaut de Symfony est Default
        }
        if(!$password){
            $form = $this->createForm(UserType::class, $user, $options);
            $form->submit($request->request->all(), $clearMissing);
            if ($form->isValid()) {
                $em = $this->get('doctrine.orm.entity_manager');
                $em->merge($user);
                $em->flush();
                return $user;
            } else {
                return $form;
            }
        }
        else{
            $encoder = $this->get('security.password_encoder');
            $validPassword = $encoder->isPasswordValid($user, $request->get('oldPassword'));
            if($validPassword){
                $encoder = $this->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $request->get('plainPassword'));
                $user->setPassword($encoded);
                $em = $this->get('doctrine.orm.entity_manager');
                $em->merge($user);
                $em->flush();
                return $user;
            }
            else{
                return $this->invalidPassword();
            }
        }
    }

    /**
    * @Rest\View()
    * @Rest\Post("/encodePassword")
    */
    //Fonction encodant un mot de passe donné
    public function encodeMotDePasseAction(Request $request){
        $user = new User();
        $encoder = $this->get('security.password_encoder');
            // le mot de passe en claire est encodé avant la sauvegarde
        $encoded = $encoder->encodePassword($user, $request->get('password'));
        return $encoded;
    }

    //Mauvais mot de passe
    private function invalidPassword()
    {
        throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid Password');
    }

    //Fonction qui retourne une exception si l'utilisateur n'est pas le bon
    private function userNotPermit(){
        throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Bad user',null, 403);
    }

    //Fonction qui retourne une exception lorsqu'un utilisateur n'est pas trouvé
    private function userNotFound()
    {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('User not found');
    }
}
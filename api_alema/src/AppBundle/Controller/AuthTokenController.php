<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest; // alias pour toutes les annotations
use AppBundle\Form\Type\CredentialsType;
use AppBundle\Entity\AuthToken;
use AppBundle\Entity\Credentials;

class AuthTokenController extends Controller
{
    /**
     * @Rest\View(statusCode=Response::HTTP_CREATED, serializerGroups={"auth-token"})
     * @Rest\Post("/auth-tokens")
     */
    public function postAuthTokensAction(Request $request)
    {
        $credentials = new Credentials();
        $form = $this->createForm(CredentialsType::class, $credentials);

        $form->submit($request->request->all());

        if (!$form->isValid()) {
            return $form;
        }

        $em = $this->get('doctrine.orm.entity_manager');

        $user = $em->getRepository('AppBundle:User')
            ->findOneByLogin($credentials->getLogin());

        if (!$user) { // L'utilisateur n'existe pas
            return $this->invalidCredentials();
        }
        if ($user->getBlocked() == true){ //L'utilisateur est bloqué
            return $this->blocked();
        }
        
        $encoder = $this->get('security.password_encoder');
        $isPasswordValid = $encoder->isPasswordValid($user, $credentials->getPassword());

        if (!$isPasswordValid) { // Le mot de passe n'est pas correct
            return $this->invalidCredentials();
        }

        $authToken = new AuthToken();
        $authToken->setValue(base64_encode(random_bytes(50)));
        $authToken->setCreatedAt(new \DateTime('now'));
        $authToken->setUser($user);

        $em->persist($authToken);
        $em->flush();

        return $authToken;
    }

    /**
     * @Rest\View(statusCode=Response::HTTP_NO_CONTENT)
     * @Rest\Delete("/auth-tokens/{login}")
     */
    public function removeAuthTokenAction(Request $request)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $authTokens = $em->getRepository('AppBundle:AuthToken')
                    ->findByUser($request->get('login'));
        /* @var $authToken AuthToken */

        $connectedUser = $this->get('security.token_storage')->getToken()->getUser();
        foreach($authTokens as $authToken){
            if ($authToken && $authToken->getUser()->getLogin() === $connectedUser->getLogin()) {
                $em->remove($authToken);
                $em->flush();
            } else {
                throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Bad Request");
            }
        }
    }

    /**
    * @Rest\View()
    * @Rest\Options("/auth-tokens")
    */
    public function OptionAuthToken(Request $request){
        return null;
    }

    private function invalidCredentials()
    {
        throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid Credentials');
    }

    private function blocked()
    {
        throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('blocked');
    }
}
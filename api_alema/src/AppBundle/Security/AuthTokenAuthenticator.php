<?php
namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthTokenAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{

    protected $httpUtils;

    public function __construct(HttpUtils $httpUtils)
    {
        $this->httpUtils = $httpUtils;
    }

    public function createToken(Request $request, $providerKey)
    {

        if(preg_match('/\/admin/', $request->getPathInfo()) === 1){
            return;
        }
        if($this->httpUtils->checkRequestPath($request, '/login_check')){
            return;
        }
        if($this->httpUtils->checkRequestPath($request, '/logout')){
            return;
        }
        $targetUrl = '/auth-tokens';
        // Si la requête est une création de token, aucune vérification n'est effectuée
        if ($request->getMethod() === "POST" && $this->httpUtils->checkRequestPath($request, $targetUrl)) {
            return;
        }

        //Pour un oublie de mot de passe
        if ($request->getMethod() === "POST" && preg_match('/\/users\/lost\//', $request->getPathInfo()) === 1) {
            return;
        }
        //Pour une inscription
        if($request->getMethod() === "POST" && ($this->httpUtils->checkRequestPath($request, '/relatives') || $this->httpUtils->checkRequestPath($request, '/users'))){
            return;
        }

        //Pour la brochure d'été
        if($this->httpUtils->checkRequestPath($request, '/brochure-summer')){
            return;
        }

        //Pour la brochure d'hiver
        if($this->httpUtils->checkRequestPath($request, '/brochure-winter')){
            return;
        }

        //Pour la brochure d'hiver
        if($this->httpUtils->checkRequestPath($request, '/parteners')){
            return;
        }

        //Pour avoir tous les séjours dans la partie globale
        if ($request->getMethod() === "GET" && $this->httpUtils->checkRequestPath($request, '/trips')) {
            return;
        }

        //Pour encoder un mot de passe
        if ($request->getMethod() === "POST" && $this->httpUtils->checkRequestPath($request, '/encodePassword')) {
            return;
        }

        $authTokenHeader = $request->headers->get('X-Auth-Token');

        if (!$authTokenHeader) {
            throw new BadCredentialsException('X-Auth-Token header is required');
        }

        return new PreAuthenticatedToken(
            'anon.',
            $authTokenHeader,
            $providerKey
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if (!$userProvider instanceof AuthTokenUserProvider) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The user provider must be an instance of AuthTokenUserProvider (%s was given).',
                    get_class($userProvider)
                )
            );
        }

        $authTokenHeader = $token->getCredentials();
        $authToken = $userProvider->getAuthToken($authTokenHeader);

        if (!$authToken) {
            throw new BadCredentialsException('Invalid authentication token');
        }

        $user = $authToken->getUser();
        $pre = new PreAuthenticatedToken(
            $user,
            $authTokenHeader,
            $providerKey,
            $user->getRoles()
        );

        // Nos utilisateurs n'ont pas de role particulier, on doit donc forcer l'authentification du token
        $pre->setAuthenticated(true);

        return $pre;
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // Si les données d'identification ne sont pas correctes, une exception est levée
        throw $exception;
    }
}
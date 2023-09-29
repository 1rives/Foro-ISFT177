<?php

namespace App\Security;

use App\Entity\Configuracion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;
    private $em;
    private $authorizationChecker;

    public function __construct(UrlGeneratorInterface $urlGenerator, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->urlGenerator = $urlGenerator;
        $this->em = $em;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username', '');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        //$status = $this->em->getRepository(User::class)->find(1)->isRegistro();

        if (!$user) :
            throw new CustomUserMessageAuthenticationException('Usuario no registrado.');
        endif;

        /*if ($user->getRole() != 'ROLE_SISTEMAS' && !$status) :
            throw new CustomUserMessageAuthenticationException('Login deshabilitado.');
        endif;

        if (!$user->isIsverified()) :
            throw new CustomUserMessageAuthenticationException('Usuario no verificado. Por favor revise su email para activar su cuenta.');
        endif;*/

        $status = $user->getAccountStatus();

        if ($status != 2) :
            throw new CustomUserMessageAuthenticationException('Cuenta inhabilitada.');
        endif;

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('_password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {

        $user = $token->getUser();

        /*if ($this->authorizationChecker->isGranted('ROLE_REGISTRADO', $user)) {
            return new RedirectResponse($this->urlGenerator->generate('app_usuario_datos'));
        }

        // Esta porción de código redirecciona a la página que quiso visitar un usuario anónimo antes de loguear.
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }*/

        // For example:
        // return new RedirectResponse($this->urlGenerator->generate('some_route'));
        //throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
        return new RedirectResponse($this->urlGenerator->generate('app_post'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}

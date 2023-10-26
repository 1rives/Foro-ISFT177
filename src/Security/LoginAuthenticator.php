<?php

namespace App\Security;

use App\Entity\Configuracion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
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
    private EntityManagerInterface $em;
    private AuthorizationCheckerInterface $authorizationChecker;
    private EmailVerifier $emailVerifier;
    private RedirectController $redirect;

    public function __construct(RedirectController $redirect, UrlGeneratorInterface $urlGenerator, EntityManagerInterface $em, AuthorizationCheckerInterface $authorizationChecker, EmailVerifier $emailVerifier)
    {
        $this->urlGenerator = $urlGenerator;
        $this->em = $em;
        $this->authorizationChecker = $authorizationChecker;
        $this->emailVerifier = $emailVerifier;
        $this->redirect = $redirect;
    }
    public function authenticate(Request $request): Passport
    {
        //Se obtiene el nombre de usuario/email enviado desde el formulario del login
        $email = $request->request->get('_username', '');

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        //Se traen todos los datos del usuario con ese username/email
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        //Si no devuelve nada es porque el usuario no está registrado
        if (!$user) :
            throw new CustomUserMessageAuthenticationException('Usuario no registrado.');
        endif;

        //En el caso de que se encuentre el usuario registrado, se trae su estado.
        $status = $user->getAccountStatus();

        //Si el estado es diferente de 2, quiere decir que la cuenta del usuario no está habilitada
        //y se le debe enviar un método para que la active mediante el mail
        if ($status == 1) :

            // Genero el correo a enviar en caso de validación
            $validationEmail = (new TemplatedEmail())
                ->from(new Address('foroisft@gmail.com', 'Foro ISFT 177'))
                ->to($user->getEmail())
                ->subject('Verificá tu correo')
                ->htmlTemplate('emails/confirmation_email.html.twig');
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user, $validationEmail);

            // Añadir mensajes flash
            $session = $request->getSession();
            $session->getFlashBag()->add(
                'notify',
                'Se ha enviado un enlace para verificar tu cuenta al correo registrado.'
            );

            // Ya que no puedo acceder a redirectToRoute, llamo al controlador RedirectController
            //$this->redirect->urlRedirectAction($request,'/login');
            throw new CustomUserMessageAuthenticationException();
        endif;

        //En caso de que el usuario se encuntre registrado y la cuenta esté habilitada
        //se verifica si escribió correctamente la contraseña
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

        //Si la contraseña es correcta, se lo reedirecciona al index de los posts
        return new RedirectResponse($this->urlGenerator->generate('app_post'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}

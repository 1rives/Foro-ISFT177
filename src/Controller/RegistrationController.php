<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    private SluggerInterface $slugger;

    private EntityManagerInterface $em;

    public function __construct(EmailVerifier $emailVerifier, SluggerInterface $slugger, EntityManagerInterface $em)
    {
        $this->emailVerifier = $emailVerifier;
        $this->slugger = $slugger;
        $this->em = $em;
    }

    /**
     * Crea el formulario para el registro del usuario despues de realizar
     * la verificación mediante el DNI del usuario.
     *
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @return Response
     * @throws TransportExceptionInterface
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): Response
    {
        $userData = new User();
        $registration_form = $this->createForm(RegistrationFormType::class, $userData);
        $registration_form->handleRequest($request);

        if ($registration_form->isSubmitted() && $registration_form->isValid()) {
            // Obtengo los datos necesarios para validar el usuario
            $submittedDNI = $registration_form->get('dni')->getData();
            $foundUser = $userRepository->findOneBy(array('dni' => $submittedDNI));
            $foundUserStatus = $foundUser?->getAccountStatus();

            // Genero el correo a enviar en caso de validación
            $validationEmail = (new TemplatedEmail())
                ->from(new Address('no-reply@foroisft177.com', 'Foro ISFT 177'))
                ->to($foundUser->getEmail())
                ->subject('Verificá tu correo')
                ->htmlTemplate('emails/confirmation_email.html.twig');

            // Si el usuario no existe
            if(!$foundUser) {
                $this->addFlash('error', 'El DNI no está registrado, si sos alumno contactate con un administrador.');
                return $this->redirectToRoute('app_register');
            }

            // Si el usuario no está validado
            if($foundUserStatus === 1) {
                $this->emailVerifier->sendEmailConfirmation('app_verify_email', $foundUser, $validationEmail);

                $this->addFlash('notify', 'Se ha enviado un enlace para verificar tu cuenta (La cuenta ya existe).');
                return $this->redirectToRoute('app_register');
            }

            // Si el usuario ya existe
            if($foundUserStatus === 2) {
                $this->addFlash('error', 'Ya se encuentra una cuenta registrada con el DNI.');
                return $this->redirectToRoute('app_register');
            }

            // Variables a utilizar
            $formAvatar = $registration_form->get('photo')->getData();
            $formPassword = $registration_form->get('password')->getData();
            $formEmail = $registration_form->get('email')->getData();
            $formDescription = $registration_form->get('description')->getData();
            $userRole = ["ROLE_USER"];
            $unverifiedAccountStatus = 1;

            // Avatar
            if(!$formAvatar) {
                $foundUser->setPhoto(NULL);
            } else {
                $newAvatarFilename = $this->convertFilenameToSafe($formAvatar);
                $pathParameter = $this->getParameter('files_directory');
                $this->moveFileToDirectory($formAvatar, $newAvatarFilename, $pathParameter);

                $foundUser->setPhoto($newAvatarFilename);
            }

            // Contraseña
            $hashedPassword = $passwordHasher->hashPassword($userData, $formPassword);
            $foundUser->setPassword($hashedPassword);

            // Variables faltantes
            $foundUser->setEmail($formEmail);
            $foundUser->setDescription($formDescription);
            $foundUser->setRoles($userRole);
            $foundUser->setAccountStatus($unverifiedAccountStatus);

            $this->em->flush();

            // Genero URL y envio verificación a User
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $foundUser, $validationEmail);

            $this->addFlash('success', 'Se ha enviado un enlace de verificación al correo.');
            return $this->redirectToRoute('app_login');
        }

        //$this->addFlash('error', 'El DNI no está registrado, si sos alumno contactate con un administrador.');
        return $this->render('registration/index.html.twig', [
            'registration_form' => $registration_form->createView(),
        ]);
    }

    /**
     * Valida el enlace de verificación enviado por correo y habilita la cuenta
     * de ser correcto.
     *
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');
        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);
        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // Valida la cuenta del usuario
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Se ha verificado tu cuenta correctamente, ahora puedes loguearte.');
        return $this->redirectToRoute('app_login');
    }

    /**
     * Mueve la imagen al directorio deseado
     *
     * La ubicaición del directorio de imágenes se encuentra
     * definido como parámetro en services.yaml
     *
     * @param mixed $file Archivo
     * @param string $newFilename Nombre de archivo formateado
     * @param string $pathParameter Directorio donde se guardará la imagen
     * @return void
     * @throws Exception
     */
    protected function moveFileToDirectory(mixed $file, string $newFilename, string $pathParameter): void
    {
        try {
            $file->move(
                $pathParameter,
                $newFilename
            );
        } catch (FileException $e) {
            throw new Exception('Ha habido un problema con su archivo');
        }
    }

    /**
     * Sanitiza el nombre actual del archivo por cuestiónes
     * de seguridad
     *
     * @param mixed $file
     * @return string
     */
    private function convertFilenameToSafe(mixed $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);

        return $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
    }
}

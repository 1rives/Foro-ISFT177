<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Exception;
use phpDocumentor\Reflection\Type;
use PhpParser\Error;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;
use function PHPUnit\Framework\throwException;


class UserController extends AbstractController
{

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

//    /**
//     * Crea el formulario para el registro del usuario despues de realizar
//     * la verificación mediante el DNI del usuario.
//     *
//     * @param Request $request
//     * @param UserPasswordHasherInterface $passwordHasher
//     * @param SluggerInterface $slugger
//     * @return Response
//     * @throws Exception
//     */
//    #[Route('/registration', name: 'register')]
//    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger): Response
//    {
//        $userData = new User();
//        $registration_form = $this->createForm(UserType::class, $userData);
//        $registration_form->handleRequest($request);
//
//        if ($registration_form->isSubmitted() && $registration_form->isValid()) {
//            $submittedDNI = $registration_form->get('dni')->getData();
//            $foundUser = $this->em->getRepository(User::class)->findOneBy(array('dni' => $submittedDNI));
//            $foundUserStatus = $foundUser?->getAccountStatus();
//
//            // Si el usuario existe
//            if(!$foundUser) {
//                return $this->render('user/register/index.html.twig', [
//                    'registration_form' => $registration_form->createView(),
//                    'error' => 'El DNI no está registrado, si sos alumno contactate con un administrador.'
//                ]);
//            }
//
//            // Para evitar el reenvio de formulario, se debe cambiar render a redirectToRoute
//            // Se debe encontrar primero la manera de enviar el mensaje de error al front.
//            switch($foundUserStatus) {
//                case 1:
//                    // TODO: Redirigir a verificación o enviar link a correo
//                    return $this->render('user/register/index.html.twig', [
//                        'registration_form' => $registration_form->createView(),
//                        'error' => 'Ya se encuentra una cuenta registrada con el DNI.'
//                    ]);
//
//                case 2:
//                    return $this->render('user/register/index.html.twig', [
//                        'registration_form' => $registration_form->createView(),
//                        'error' => 'Ya se encuentra una cuenta registrada con el DNI.'
//                    ]);
//            }
//
//            // Variables a utilizar
//            $formAvatar = $registration_form->get('photo')->getData();
//            $formPassword = $registration_form->get('password')->getData();
//            $formEmail = $registration_form->get('email')->getData();
//            $formDescription = $registration_form->get('description')->getData();
//            $userRole = ["ROLE_USER"];
//            $unverifiedAccountStatus = 1;
//
//            // Avatar
//            if(!$formAvatar) {
//                $foundUser->setPhoto(NULL);
//            } else {
//                $newAvatarFilename = $this->convertFilenameToSafe($formAvatar, $slugger);
//                $pathParameter = $this->getParameter('files_directory');
//                $this->moveFileToDirectory($formAvatar, $newAvatarFilename, $pathParameter);
//
//                $foundUser->setPhoto($newAvatarFilename);
//            }
//
//            // Contraseña
//            $hashedPassword = $passwordHasher->hashPassword($userData, $formPassword);
//            $foundUser->setPassword($hashedPassword);
//
//            // Variables faltantes
//            $foundUser->setEmail($formEmail);
//            $foundUser->setDescription($formDescription);
//            $foundUser->setRoles($userRole);
//            $foundUser->setAccountStatus($unverifiedAccountStatus);
//
//            $this->em->flush();
//
//            return $this->redirectToRoute('app_login');
//        }
//
//        return $this->render('user/register/index.html.twig', [
//            'registration_form' => $registration_form->createView(),
//        ]);
//    }

    /**
     * Devuelve la información requerida del User a la
     * vista del perfil.
     *
     * @param User $user
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route('/profile/{id}', name: 'userProfile')]
    public function userProfile(User $user, UserRepository $userRepository) {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $userId = $user->getId();
        $userData = $userRepository->findUserProfileData($userId);

        return $this->render('user/profile/index.html.twig', [
            'user' => $userData
        ]);
    }


}

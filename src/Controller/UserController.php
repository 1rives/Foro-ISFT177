<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;


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

    /**
     * Crea el formulario para el registro del usuario despues de realizar
     * la verificación mediante el DNI del usuario.
     *
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @return Response
     * @throws \Exception
     */
    #[Route('/registration', name: 'register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, SluggerInterface $slugger): Response
    {
        $user = new User();
        $registration_form = $this->createForm(UserType::class, $user);
        $registration_form->handleRequest($request);

        if ($registration_form->isSubmitted() && $registration_form->isValid()) {

            // Encripto contraseña
            $plainTextPassword = $registration_form->get('password')->getData();

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainTextPassword
            );

            $avatar = $registration_form->get('photo')->getData();

            if(!$avatar) {
                $user->setPhoto(NULL);
            } else {
                $newAvatarFilename = $this->convertFilenameToSafe($avatar, $slugger);

                $pathParameter = $this->getParameter('files_directory');
                $this->moveFileToDirectory($avatar, $newAvatarFilename, $pathParameter);

                $user->setPhoto($newAvatarFilename);
            }


            $user->setPassword($hashedPassword);
            $user->setRoles(["ROLE_USER"]);

            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute('app_login');
        }
        return $this->render('user/index.html.twig', [
            'registration_form' => $registration_form->createView(),
        ]);
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
     * @throws \Exception
     */
    protected function moveFileToDirectory(mixed $file, string $newFilename, string $pathParameter): void
    {
        try {
            $file->move(
                $pathParameter,
                $newFilename
            );
        } catch (FileException $e) {
            throw new \Exception('Ha habido un problema con su archivo');
        }
    }

    /**
     * Sanitiza el nombre actual del archivo por cuestiónes
     * de seguridad
     *
     * @param mixed $file
     * @param SluggerInterface $slugger Interface de Slugger
     * @return string
     */
    private function convertFilenameToSafe(mixed $file, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);

        return $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
    }

}

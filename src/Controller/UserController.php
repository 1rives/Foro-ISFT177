<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PostController extends AbstractController
{
    private $em;

    /**
     * @param $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/post/{id}', name: 'app_post')]
    public function index($id): Response
    {
        // Busca el post con ID de 1
        $posts = $this->em->getRepository(Post::class)->findBy(['id' => 1]);

        // Llama a la función custom findPost
        $custom_post = $this->em->getRepository(Post::class)->findPost($id);

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'custom_post' => $custom_post
        ]);
    }

    /**
     * Inserta un nuevo post en la base de datos
     *
     * @return JsonResponse
     */
    #[Route('/insert/post', name: 'insert_post')]
    public function insert() {
        $post = new Post('Post insertado',
            'testing',
            'Creado mediante el Entity Manager de Symfony',
            'supafile.exe',
            'insert_test');
        $user = $this->em->getRepository(User::class)->find(1);

        $post->setTitle('Post insertado')
            ->setUser($user);

        // Crea una instancia persistente
        $this->em->persist($post);

        // Guarda en la base de datos
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }
}

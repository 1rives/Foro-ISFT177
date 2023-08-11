<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/', name: 'app_post')]
    public function index(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        $posts = $this->em->getRepository(Post::class)->findAllPosts();

        if($form->isSubmitted() && $form->isValid()) {
            $url = str_replace(" ", "-", $form->get('title')->getData());
            $post->setUrl(strtolower($url));

            // Especifico el usuario siendo el único dato faltante
            $user = $this->em->getRepository(User::class)->find(1);
            $post->setUser($user);

            $this->em->persist($post);
            $this->em->flush();
            
            return $this->redirectToRoute('app_post');
        }

        return $this->render('post/index.html.twig', [
            'postForm' => $form->createView(),
            'posts' => $posts
        ]);
    }

    /**
     * Inserta un nuevo post en la base de datos
     *
     * @return JsonResponse
     */
//    #[Route('/insert/post', name: 'insert_post')]
//    public function insert() {
//        $post = new Post('Post insertado',
//            'testing',
//            'Creado mediante el Entity Manager de Symfony',
//            'supafile.exe',
//            'insert_test');
//        $user = $this->em->getRepository(User::class)->find(1);
//
//        $post->setTitle('Post insertado')
//            ->setUser($user);
//
//        // Crea una instancia persistente
//        $this->em->persist($post);
//
//        // Guarda en la base de datos
//        $this->em->flush();
//
//        return new JsonResponse(['success' => true]);
//    }
}

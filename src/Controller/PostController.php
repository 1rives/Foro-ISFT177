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
use Symfony\Component\String\Slugger\SluggerInterface;

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

    /**
     * @throws \Exception
     */
    #[Route('/', name: 'app_post')]
    public function index(Request $request, SluggerInterface $slugger): Response
    {
        $post = new Post();

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        $posts = $this->em->getRepository(Post::class)->findAllPosts();

        if($form->isSubmitted() && $form->isValid()) {
            // Trae el archivo del post
            $file = $form->get('file')->getData();

            if($file) {
                $newFilename = $this->convertFilenameToSafe($file, $slugger);
                $this->moveFileToDirectory($file, $newFilename, $post);
            }

            // Crea el URL mediante el título
            $url = $form->get('title')->getData();
            $post->setUrl($this->formatPostURL($url));

            // Especifico el usuario siendo el único dato faltante
            // TODO: Investigar por qué es necesario ID 1
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
     * Devuelve todos los detalles de un post por medio de la ID.
     *
     * @param Post $post
     * @return Response
     */
    #[Route('/post/details/{id}', name: 'postDetails')]
    public function postDetails(Post $post): Response
    {
        return $this->render('post/post-details.html.twig', ['post' => $post]);
    }

    /**
     * Formatea el título de un Post para su utilización
     *
     * @param $title string Título del Post
     * @return string String formateado
     */
    private function formatPostURL(string $title): string
    {
        $formattedTitle = str_replace(' ', '-', $title);
        return strtolower($formattedTitle);
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

    /**
     * Mueve la imagen al directorio de imagenes
     *
     * La ubicaición del directorio de imágenes se encuentra
     * definido como parámetro en services.yaml
     *
     * @param mixed $file Archivo
     * @param string $newFilename Nombre de archivo formateado
     * @param Post $post Entidad de Post
     * @return void
     * @throws \Exception
     */
    private function moveFileToDirectory(mixed $file, string $newFilename, Post $post): void
    {
        try {
            $file->move(
                $this->getParameter('files_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            throw new \Exception('Ha habido un problema con su archivo');
        }

        $post->setFile($newFilename);
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

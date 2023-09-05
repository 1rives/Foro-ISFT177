<?php

namespace App\Controller;

use App\Entity\Interaction;
use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Form\InteractionType;
use App\Repository\InteractionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Error;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
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
    public function index(Request $request, SluggerInterface $slugger, PaginatorInterface $paginator): Response
    {
        $post = new Post();

        // Traigo los posts e inicializo paginación
        $query = $this->em->getRepository(Post::class)->findAllPosts();
        $pagination = $this->getPagination($paginator, $query, $request);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);



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
            'posts' => $pagination
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
        $comments = $this->em->getRepository(Interaction::class)->findPostComments($post->getId());

        return $this->render('post/post_details.html.twig', [
            'post' => $post,
            'comments' => $comments
        ]);
    }

    /**
     * Función para comentar los posts
     * @throws \Exception
     */
    #[Route('/commentPost', name: 'commentPost')]
    public function commentPost(Request $request): Response
    {
        $interaction = new Interaction();

        //Recoger POST
        $comment = $request->request->get("comment");

        $postId = $request->request->get("post-id");
        $post = $this->em->getRepository(Post::class)->find($postId);
        $userId = $request->request->get("user-id");
        $user = $this->em->getRepository(User::class)->find($userId);

        if ($comment) {

            $interaction->setComment($comment);
            $interaction->setUser($user);
            $interaction->setPost($post);

            $this->em->persist($interaction);
            $this->em->flush();
            
            // Redirecciona al post
            $postRoute = $request->headers->get('referer');
            
            return $this->redirect($postRoute);
        }

        return $this->redirect('app_post');
    }

     /**
      * me gusta 
     * @throws \Exception
     */
    #[Route('/likes', name: 'likes', options: ['expose' => true])]
    public function Like(Request $request, UserInterface $userInterface) {
        if($request->isXmlHttpRequest()){
            $currentUserID = $userInterface->getUserIdentifier();
            $id = $request->request->get('id');
            $post = $this->em->getRepository(Post::class)->find($id);
            $likes = $post->getLikes();
            $likes .= $currentUserID .',';
            $post->setLikes($likes);
            $this->em->flush();
            return new JsonResponse(['likes' => $likes]);
        }else {
            throw new \Exception('Error');
        }
    }


    /**
     * Inicializa la páginación de los Posts.
     *
     * @param PaginatorInterface $paginator     Interfáz del paginador Knp
     * @param Query $query                      Query que contiene los Posts
     * @param Request $request                  Interfáz del Request
     * @return PaginationInterface              Inicialización de la paginación
     */
    public function getPagination(PaginatorInterface $paginator,Query $query, Request $request): PaginationInterface
    {
        return $paginator->paginate(
            $query, /* Se usa la query ya que result trae TODOS los Posts */
            $request->query->getInt('page', 1), /* Página por default */
            2 /* Límite de Post por página */
        );
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

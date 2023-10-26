<?php

namespace App\Controller;

use App\Entity\Interaction;
use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Form\InteractionType;
use App\Repository\PostRepository;
use App\Repository\InteractionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Query;
use Error;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\Date;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class PostController extends AbstractController
{
    private $em;

    /**
     * @var Security
     */
    private $security;

    /**
     * @param $em
     */
    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Trae todos los Posts disponibles con la correspondiente paginación.
     *
     * Además, el usuario puede crear un nuevo Post.
     *
     * @param Request $request
     * @param SluggerInterface $slugger
     * @param PaginatorInterface $paginator
     * @return Response
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
            $this->denyAccessUnlessGranted('ROLE_USER');

            // Trae el archivo del post
            $file = $form->get('file')->getData();

            if($file) {
                $newFilename = $this->convertFilenameToSafe($file, $slugger);
                $pathParameter = $this->getParameter('files_directory'); // Ubicación de directorio
                $this->moveFileToDirectory($file, $newFilename, $pathParameter);
                $post->setFile($newFilename);
            }

            // Crea el URL mediante el título
            $url = $form->get('title')->getData();
            $post->setUrl($this->formatPostURL($url));

            // Especifico el usuario
            // Se toma el id del usuario logeado
            $userId = $this->security->getUser()->getId();
            // Se asigna el id a la publicación del post
            $user = $this->em->getRepository(User::class)->find($userId);
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
     * @param InteractionRepository $interactionRepository
     * @return Response
     */
    #[Route('/post/details/{id}', name: 'postDetails')]
    public function postDetails(Post $post, InteractionRepository $interactionRepository): Response
    {
        $postId = $post->getId();

        $comments = $interactionRepository->findPostComments($postId);
        $commentsWithNames = $this->addUserNameToArrayElements($comments);

        // Ordeno los comentarios
        usort($commentsWithNames, function($a, $b) {
            return $b['creation_date'] <=> $a['creation_date'];
        });

        return $this->render('post/post_details.html.twig', [
            'post' => $post,
            'comments' => $commentsWithNames
        ]);
    }

    /**
     * Función para comentar los posts
     *
     * @param Request $request
     * @return Response
     */
    #[Route('/commentPost', name: 'commentPost')]
    public function commentPost(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $interaction = new Interaction();

        $comment = $request->request->get("comment");
        $postId = $request->request->get("post-id");
        $post = $this->em->getRepository(Post::class)->find($postId);
        $userId = $request->request->get("user-id");
        $user = $this->em->getRepository(User::class)->find($userId);

        // Redirección a la página anterior
        $postRoute = $request->headers->get('referer');

        if ($comment) {
            $interaction->setComment($comment);
            $interaction->setUser($user);
            $interaction->setPost($post);
            $interaction->setCreationDate(New \DateTime());

            $this->em->persist($interaction);
            $this->em->flush();

            return $this->redirect($postRoute);
        }

        return $this->redirect($postRoute);
    }

    /**
     * Dar me gusta al Post
     * SIN UTILIZAR
     *
     * @param Request $request
     * @param UserInterface $userInterface
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route('/likes', name: 'likes', options: ['expose' => true])]
    public function Like(Request $request, UserInterface $userInterface)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

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
            return $this->redirectToRoute('app_post');
        }
    }

    /**
     * Editar el comentario de un Post mediante una consulta
     * AJAX, realizando las validaciones necesarias.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route('/comment/edit', name: 'comment_edit', options: ['expose' => true])]
    public function editComment(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $defaultErrorMessage = 'Ha habido un error al procesar tu comentario, intente de nuevo.';
        $successMessage = 'El comentario ha sido actualizado con éxito.';

        if($request->isXmlHttpRequest()){
            // Obtengo la URL del post
            $userCurrentUrl = $request->headers->get('referer');

            $urlPath = parse_url($userCurrentUrl, PHP_URL_PATH);
            $urlPathArray = explode('/' ,$urlPath);

            $postId = end($urlPathArray);
            $commentId = $request->request->get('id');

            // VALIDACIONES

            // 1. Ruta correcta, esta siendo post/details/
            // TODO: Reemplazar ruta hardcodeada
            if(!str_contains($urlPath, '/post/details/')) {
                return new JsonResponse( array('status' => 'error', 'message' => $defaultErrorMessage) );
            }

            // 2. Que el Post exista mediante su id
            $foundPost = $this->em->getRepository(Post::class)->find($postId);
            if(!$foundPost) {
                return new JsonResponse( array('status' => 'error', 'message' => $defaultErrorMessage) );
            }

            // 3. Id de comentario exista en ID Post
            $postComment = $this->em->getRepository(Interaction::class)->find($commentId);

            if(!$postComment) {
                return new JsonResponse( array('status' => 'error', 'message' => $defaultErrorMessage) );
            }

            // 4. El usuario tiene que ser el mismo creador del comentario
            $user = $this->getUser();
            $userId = $user->getUserIdentifier();
            $commentUserId = $postComment->getUser()->getId();

            if($commentUserId != $userId) {
                return new JsonResponse( array('status' => 'error', 'message' => $defaultErrorMessage) );
            }

            // Genero los datos y actualizo el comentario
            $newComment = $request->request->get('comment');
            $updatedDate = New \DateTime();

            $postComment->setComment($newComment);
            $postComment->setCreationDate($updatedDate);

            $this->em->flush();

            return new JsonResponse(array('status' => 'success', 'message' => 'Se ha editado el comentario exitosamente'));
        }else {
            return $this->redirectToRoute('app_post');
        }
    }

    /**
     * Agrega el nombre completo del User en cada elemento del arreglo
     * mediante una key.
     *
     * Si no se encuentra un nombre o apellido registrado del User, se le asignará
     * un nombre por defecto.
     *
     * @param array $array      Arreglo con arreglos de usuario
     * @return array
     */
    private function addUserNameToArrayElements(array $array): array {
        return array_map(function ($arrayElement) {
            $userId = $arrayElement['user_id'];
            $user = $this->em->getRepository(User::class)->find($userId);

            // Si falta un campo, nombre predeterminado
            (!$user->getFirstName() || !$user->getLastName()) ?
                $userName = "Usuario" :
                $userName = $user->getFirstName() . " " . $user->getLastName();

            $arrayElement['user_full_name'] = $userName;
            return $arrayElement;
        }, $array);
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
            6 /* Límite de Post por página */
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

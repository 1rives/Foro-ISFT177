<?php

namespace App\Repository;

use App\Entity\Interaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interaction>
 *
 * @method Interaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Interaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Interaction[]    findAll()
 * @method Interaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interaction::class);
    }

    /**
     * Devuelve todos los comentarios de un post
     *
     * @param $postId int ID de un Post
     * @return mixed
     */
    public function findPostComments($postId): mixed
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT 
                interaction.id,
                interaction.comment,
                user.id AS user_id,
                user.email AS user_email,
                user.photo AS user_avatar
                FROM App:Interaction interaction
                JOIN interaction.post post
                JOIN interaction.user user
                WHERE post.id = '.$postId.'
            ')
            ->getResult();
    }

//
//    public function findPostComments($postId): mixed
//    {
//        return $this->getEntityManager()
//            ->createQuery('
//                SELECT
//                    interaction.id
//                    interaction.user_favorite
//                    interaction.comment
//                    post.id AS post_id
//                    user.id AS user_id
//                    user.email AS user_username
//                    user.photo AS user_avatar
//                FROM App\Entity\Interaction interaction
//                JOIN App\Entity\Post post WITH interaction.post_id = post.id
//                JOIN App\Entity\User user WITH interaction.user_id = user.id
//                    WHERE interaction.post_id = :postid
//            ')
//            ->setParameter('postid', $postId)
//            ->getResult();
//    }

//    /**
//     * @return Interaction[] Returns an array of Interaction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Interaction
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

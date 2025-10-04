<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find users by one or more roles.
     *
     * Example:
     *   $userRepo->findByRoles(['ROLE_ADMIN', 'ROLE_FIRM']);
     */
    public function findByRoles(array $roles): array
    {

        $conn = $this->getEntityManager()->getConnection();

        // Build dynamic WHERE clause with JSON_CONTAINS
        $conditions = [];
        $params = [];
        foreach ($roles as $index => $role) {
            $param = ":role{$index}";
            $conditions[] = "JSON_CONTAINS(u.roles, $param) = 1";
            $params[$param] = json_encode($role);
        }

        $sql = sprintf(
            'SELECT * FROM users u WHERE %s',
            implode(' OR ', $conditions)
        );

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery($params)->fetchAllAssociative();

        // Hydrate array results into User entities
        return $this->getEntityManager()
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', array_column($result, 'id'))
            ->getQuery()
            ->getResult();

        // ag: commented out logic would return a plain array
        // $conn = $this->getEntityManager()->getConnection();

        // // Build OR conditions for each role
        // $conditions = [];
        // foreach ($roles as $i => $role) {
        //     $conditions[] = "JSON_CONTAINS(u.roles, :role{$i})";
        // }

        // $sql = sprintf(
        //     'SELECT * FROM users u WHERE %s',
        //     implode(' OR ', $conditions)
        // );

        // $stmt = $conn->prepare($sql);

        // foreach ($roles as $i => $role) {
        //     $stmt->bindValue("role{$i}", json_encode($role));
        // }

        // return $stmt->executeQuery()->fetchAllAssociative();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

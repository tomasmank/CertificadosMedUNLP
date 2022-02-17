<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findByUsername($value): User
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :val')
            ->setParameter('val',$value)
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAdmins($userID): Array
    {
        return $this->createQueryBuilder('u')
            ->where('u.id != :valID')
            ->andWhere('p.name = :valName')
            ->setParameter('valID', $userID)
            ->setParameter('valName','Administrador')
            ->innerJoin('App\Entity\Profile', 'p', 'WITH', 'u.profile = p.id')
            ->getQuery()
            ->getResult()
        ;
    }

    public function sortedUsers(): Array
    {
        return $this->findBy(array(),array(
            'username' => 'ASC'));
    }

    public function paginate($dql, $page = 1, $limit = 3)
    {
        $paginator = new Paginator($dql);
    
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1)) // Offset
            ->setMaxResults($limit); // Limite
    
        return $paginator;
    }

    public function getAll($currentPage = 1, $limit = 3, $searchParameter = null){
        
        $qb = $this->createQueryBuilder('u');

        if ($searchParameter) {
            $qb ->where('u.username LIKE ?1')
            ->orWhere('u.firstName LIKE ?1')
            ->orWhere('u.lastName LIKE ?1')
            ->setParameter(1, '%'.$searchParameter.'%');
        }
        
        $query = $qb ->addOrderBy('u.username', 'ASC')
            ->getQuery();

        $paginator = $this->paginate($query, $currentPage, $limit);

        return array('paginator' => $paginator, 'query' => $query);
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

}

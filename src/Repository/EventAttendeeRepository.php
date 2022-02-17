<?php

namespace App\Repository;

use App\Entity\EventAttendee;
use App\Entity\Event;
use App\Entity\Attendee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;


/**
 * @method EventAttendee|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventAttendee|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventAttendee[]    findAll()
 * @method EventAttendee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventAttendeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventAttendee::class);
    }

    public function sortedAttendees(Event $event) {
                
        return $this->createQueryBuilder('ea')
            ->where('ea.event = :value')
            ->setParameter('value', $event)
            ->innerJoin('App\Entity\Attendee', 'a', 'WITH', 'ea.attendee = a.id')
            ->orderBy('a.last_name', 'ASC')
            ->addOrderBy('a.first_name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function paginate($dql, $page = 1, $limit = 3)
    {
        $paginator = new Paginator($dql);
    
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1)) // Offset
            ->setMaxResults($limit); // Limite
    
        return $paginator;
    }

    public function getAttendees($event, $currentPage = 1, $limit = 3, $searchParameter = null){
        
        $qb = $this->createQueryBuilder('ea')
            ->where('ea.event = :value')
            ->setParameter('value', $event)
            ->innerJoin('App\Entity\Attendee', 'a', 'WITH', 'ea.attendee = a.id');

        if ($searchParameter) {
            $qb->andWhere('a.first_name LIKE ?1')
                ->orWhere('a.last_name LIKE ?1')
                ->orWhere('a.dni LIKE ?1')
                ->orWhere('ea.email LIKE ?1')
                ->orWhere('ea.cond LIKE ?1')
                ->setParameter(1, '%'.$searchParameter.'%');
        }
        
        $query = $qb->orderBy('a.last_name', 'DESC')
            ->addOrderBy('a.first_name', 'DESC')
            ->getQuery();

        $paginator = $this->paginate($query, $currentPage, $limit);

        return array('paginator' => $paginator, 'query' => $query);
    }

    // /**
    //  * @return EventAttendee[] Returns an array of EventAttendee objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EventAttendee
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

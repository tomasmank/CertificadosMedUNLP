<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\City;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }
    
    public function findByName($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.name = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult()
        ;
    }

     /**
      * @return Event[] Returns an array of Event objects
      */
    
   /* public function findByName(string $toSearch)
    {*/
   /*     $query = $this->getEntityManager()
            ->createQuery(
                'SELECT e FROM Event e
                 WHERE e.name = :ts'
            )->setParameter('ts', $toSearch);
            
            return $query->getResults();*/

    /*    return $this->createQueryBuilder('e')
            ->innerJoin('App\Entity\Event', 'App\Entity\City', 'c', 'e.city_id = c.id')
            ->where('e.name LIKE ?1')
            ->setParameter(1, '%'.$value.'%')
            ->getQuery()
            ->getResult()
        ;   */
    /*}*/
    
}

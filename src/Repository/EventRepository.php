<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\City;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;
use \DateTime;

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

    public function sortedEvents() {
        return $this->findBy(array(), array('published' => 'ASC', 'name' => 'ASC'));
    }

    public function findDuplicated(int $eventID, string $eventName, City $city, DateTime $startDate, DateTime $endDate)
    {
        $criteria = new Criteria();
        $criteria->andWhere($criteria->expr()->neq('id', $eventID));
        $criteria->andWhere($criteria->expr()->eq('name', $eventName));
        $criteria->andWhere($criteria->expr()->eq('city', $city));
        $criteria->andWhere($criteria->expr()->eq('startDate', $startDate));
        $criteria->andWhere($criteria->expr()->eq('endDate', $endDate));
        
        return $this->matching($criteria)->count();
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
        
        $qb = $this->createQueryBuilder('e');

        if ($searchParameter) {
            $qb->where('e.name LIKE ?1')
            ->orWhere('c.name LIKE ?1')
            ->innerJoin(
                City::class, 'c', 'WITH', 'e.city = c.id')
            ->setParameter(1, '%'.$searchParameter.'%');
        }

        $qb ->addOrderBy('e.published', 'ASC');
        $qb ->addOrderBy('e.name', 'ASC');

        $query = $qb->getQuery();

        $paginator = $this->paginate($query, $currentPage, $limit);

        return array('paginator' => $paginator, 'query' => $query);
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

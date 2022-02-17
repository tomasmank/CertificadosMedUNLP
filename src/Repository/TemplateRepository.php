<?php

namespace App\Repository;

use App\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Template|null find($id, $lockMode = null, $lockVersion = null)
 * @method Template|null findOneBy(array $criteria, array $orderBy = null)
 * @method Template[]    findAll()
 * @method Template[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    public function alreadyExists(string $templateName): bool
    {
        $criteria = new Criteria();
        $criteria->andWhere($criteria->expr()->eq('name', $templateName));
        
        if (($this->matching($criteria)->count()) == 0 ){
            $ret = False;
        } else {
            $ret = True;
        }

        return $ret;
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
        
        $qb = $this->createQueryBuilder('t');

        if ($searchParameter) {
            $qb ->where('t.name LIKE ?1')
            ->orWhere('t.comments LIKE ?1')
            ->setParameter(1, '%'.$searchParameter.'%');
        }
        
        $query = $qb ->addOrderBy('t.id', 'DESC')
            ->getQuery();

        $paginator = $this->paginate($query, $currentPage, $limit);

        return array('paginator' => $paginator, 'query' => $query);
    }

    // /**
    //  * @return Template[] Returns an array of Template objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Template
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    // /**
    //  * @return Task[] Returns an array of Task objects
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
    public function findOneBySomeField($value): ?Task
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getDistinctComplexities()
    {
        return $this->createQueryBuilder('t')
            ->select('t.complexity')
            ->groupBy('t.complexity')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    public function getDistinctProviders()
    {
        return $this->createQueryBuilder('t')
            ->select('t.provider')
            ->groupBy('t.provider')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    public function getTasksByProviderNameAndComplexity($p_name,$complexity)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.provider = :val')
            ->setParameter('val', $p_name)
            ->andWhere('t.complexity = :val2')
            ->setParameter('val2', $complexity)
            ->orderBy('t.complexity','ASC')
            ->addOrderBy('t.duration','ASC')
            ->getQuery()
            ->execute();
    }

    public function getAvgTaskDurationByProviderName($value)
    {
        return $this->createQueryBuilder('t')
            ->select('AVG(t.duration)')
            ->andWhere('t.provider = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

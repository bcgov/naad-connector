<?php

namespace Bcgov\NaadConnector\Repository;

use Bcgov\NaadConnector\Entity\Alert;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for retrieving Alerts from the database.
 *
 * @category Database
 * @package  NaadConnector
 * @author   Michael Haswell <Michael.Haswell@gov.bc.ca>
 * @license  https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link     https://www.doctrine-project.org/
 * @extends  EntityRepository<Alert>
 */
class AlertRepository extends EntityRepository
{
    /**
     * Constructs an AlertRepository object.
     *
     * @param ManagerRegistry $registry ManagerRegistry object to construct with.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    //    /**
    //     * @return Alert[] Returns an array of Alert objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Alert
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

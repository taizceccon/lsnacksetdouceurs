<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findAllRandom(int $limit = 8): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // Étape 1 : récupérer uniquement les IDs
        $sql = 'SELECT id FROM product';
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        $ids = array_column($resultSet->fetchAllAssociative(), 'id');

        // Étape 2 : tirer des IDs au hasard côté PHP
        shuffle($ids); // mélanger
        $randomIds = array_slice($ids, 0, $limit); // garder $limit éléments

        if (empty($randomIds)) {
            return [];
        }

        // Étape 3 : récupérer les entités avec leurs catégories
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $randomIds)
            ->getQuery()
            ->getResult();
    }

    public function findByCategoryName(string $name): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.category', 'c')
            ->where('c.category = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getResult();
    }

    public function searchByKeyword(string $keyword): array
    {
        // Requête simple avec LIKE sur titre et description
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->where('p.titre LIKE :kw OR p.description LIKE :kw')
            ->setParameter('kw', '%' . $keyword . '%')
            ->getQuery()
            ->getResult();
    }
}
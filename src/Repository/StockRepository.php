<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Stock|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stock|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stock[]    findAll()
 * @method Stock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function getAccountValue() {
        $stocks = $this->findAll();

        $totalCash              = 0;
        $totalStockValueLong    = 0;
        $totalStockValueShort   = 0;
        $totalStockValueOption  = 0;

        foreach ($stocks as $stock) {
            $stockValueLong     = $stock->getPrice() *$stock->getQuantity('Long');
            $stockValueShort    = $stock->getPrice() *$stock->getQuantity('Short');

            $totalStockValueLong    += $stockValueLong;
            $totalStockValueShort   += $stockValueShort;
        }

        return $totalCash +$totalStockValueLong +$totalStockValueOption -$totalStockValueShort;
    }

    public function getBuyingPower() {
        return 0;
    }

    public function getCash() {
        return 0;
    }

    public function getAnnualReturn() {
        return 0;
    }

}

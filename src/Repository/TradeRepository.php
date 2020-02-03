<?php

namespace App\Repository;

use App\Entity\Trade;
use App\Services\TradeManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Trade|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trade|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trade[]    findAll()
 * @method Trade[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TradeRepository extends ServiceEntityRepository
{
    protected $tradeManager;

    public function __construct(ManagerRegistry $registry, TradeManager $tradeManager)
    {
        $this->tradeManager = $tradeManager;

        parent::__construct($registry, Trade::class);
    }

    public function createFromRaw($raw_data) {
        $raw_data_rows = preg_split(':\n:', $raw_data);
        $trade_data = [];

        foreach ($raw_data_rows as $raw_data_row) {
            $raw_data_row = preg_replace(':[\$,\\r]:', '', $raw_data_row);
            $trade_data[] = preg_split(':\s\s+:', $raw_data_row);
        }

        $new_trades = [];

        foreach ($trade_data as $trade_data_row) {
            $trade = new Trade();
            $trade->updateTimestamps();
            $trade->setExecutedAt(new \DateTime($trade_data_row[0]));
            $trade->setDescription($trade_data_row[1]);
            $trade->setName($trade_data_row[2]);
            $trade->setQuantity($trade_data_row[3]);
            $trade->setPrice(preg_replace(':$|\s:', '', $trade_data_row[4]));
            $trade->setFee(preg_replace(':$|\s:', '', $trade_data_row[5]));
            $trade->setTotal(preg_replace(':$|\s:', '', $trade_data_row[6]));
            $trade->setTradeType($this->tradeManager->getTradeType($trade_data_row[1]));
            $trade->setOrderType($this->tradeManager->getOrderType($trade_data_row[1]));

            $stock = $this->tradeManager->createOrGetStock($trade, $trade_data_row[2]);

            $trade->setStock($stock);
            $trade->setAdjustedPrice(0);

            $new_trades[] = $trade;
        }

        return $new_trades;
    }

    /**
     * @param Trade $trade
     * @return Trade[]
     */
    public function findHistoricalRelatedTrades(Trade $trade, $symbol = null) {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.stock', 's')
            ->andWhere('s.symbol = :symbol')
            ->andWhere('t.trade_type = :tradetype')
            ->andWhere('t.executedAt <= :tradedate')
            ->orderBy('t.executedAt', 'ASC')
            ->getQuery()
            ->execute([
                'symbol'    => $trade->getStock()->getSymbol(),
                'tradetype' => $trade->getTradeType(),
                'tradedate' => $trade->getExecutedAt()
            ]);
    }

    public function getAllTrades() {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.stock', 's')
            ->addOrderBy('t.executedAt', 'DESC')
            ->addOrderBy('s.symbol', 'ASC')
            ->addOrderBy('t.quantity', 'ASC')
            ->getQuery()
            ->execute();
    }

}

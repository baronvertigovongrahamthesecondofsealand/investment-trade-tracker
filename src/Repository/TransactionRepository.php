<?php

namespace App\Repository;

use App\Entity\Trade;
use App\Entity\Transaction;
use App\Services\TradeManager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    protected $tradeManager;

    public function __construct(ManagerRegistry $registry, TradeManager $tradeManager)
    {
        $this->tradeManager = $tradeManager;

        parent::__construct($registry, Transaction::class);
    }

    public function getAll() {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.stock', 's')
            ->addOrderBy('t.executedAt', 'DESC')
            ->addOrderBy('s.symbol', 'ASC')
            ->addOrderBy('t.quantity', 'ASC')
            ->getQuery()
            ->execute();
    }

    public function createFromRaw($raw_data) {
        $raw_data_rows = preg_split(':\n:', $raw_data);
        $transaction_data = [];

        foreach ($raw_data_rows as $raw_data_row) {
            $raw_data_row = preg_replace(':[\$,\\r]:', '', $raw_data_row);
            $transaction_data[] = preg_split(':\s\s+:', $raw_data_row);
        }

        $new_records = [];

        foreach ($transaction_data as $trade_data_row) {
            $tradeType = $this->tradeManager->getTradeType($trade_data_row[1]);
            $orderType = $this->tradeManager->getOrderType($trade_data_row[1]);

            if ($tradeType == 'Option' && $orderType == 'Expired') {
                $trade = new Trade();
                $trade->updateTimestamps();
                $trade->setExecutedAt(new \DateTime($trade_data_row[0]));
                $trade->setDescription($trade_data_row[1]);
                $trade->setTradeType($tradeType);
                $trade->setQuantity($trade_data_row[3]);
                $trade->setPrice($trade_data_row[4]);
                $trade->setFee(0);
                $trade->setAdjustedPrice(0);

                $stock = $this->tradeManager->createOrGetStock($trade, $trade_data_row[2]);

                $trade->setStock($stock);
                $trade->setName($stock->getSymbol());

                $trade->setTotal($trade_data_row[5]);
                $trade->setOrderType($orderType);

                $new_records[] = $trade;

            } elseif ($tradeType == 'Interest') {
                $totalValue = preg_replace(':\((\d+\.\d+)\):', '-$1', $trade_data_row[2]);

                $transaction = new Transaction();
                $transaction->setExecutedAt(new \DateTime($trade_data_row[0]));
                $transaction->setDescription($trade_data_row[1]);
                $transaction->setTradeType($tradeType);
                $transaction->setQuantity(1);
                $transaction->setPrice($totalValue);
                $transaction->setTotalValue($totalValue);
                $transaction->setAccountValue($trade_data_row[3]);

                $transaction->setStock(null);

                $new_records[] = $transaction;

            } elseif ($tradeType == 'Dividend') {
                $totalValue = preg_replace(':(\(|\)):', '', $trade_data_row[5]);

                $transaction = new Transaction();
                $transaction->setExecutedAt(new \DateTime($trade_data_row[0]));
                $transaction->setDescription($trade_data_row[1]);
                $transaction->setTradeType($tradeType);
                $transaction->setQuantity($trade_data_row[3]);
                $transaction->setPrice($trade_data_row[4]);
                $transaction->setTotalValue($totalValue);
                $transaction->setAccountValue($trade_data_row[6]);

                if ($trade_data_row[2]) {
                    $stock = $this->tradeManager->createOrGetStock($transaction, $trade_data_row[2]);
                    $transaction->setStock($stock);
                }

                $new_records[] = $transaction;
            }
        }

        return $new_records;
    }

}

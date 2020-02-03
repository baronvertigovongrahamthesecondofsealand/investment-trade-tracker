<?php

namespace App\Services;

use App\Entity\Stock;
use App\Entity\Trade;
use Doctrine\ORM\EntityManagerInterface;

class TradeManager {

    protected $em;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->em = $entityManager;
    }

    public function getAccountValue() {
        $transactions   = $this->em->getRepository('App:Transaction')->findBy([], ['executedAt' => 'ASC']);
        $stocks         = $this->em->getRepository('App:Stock')->createQueryBuilder('s')
            ->leftJoin('s.trades', 't')
            ->orderBy('t.executedAt', 'ASC')
            ->getQuery()
            ->execute();

        $totalCash         = 0;
        $totalValueLong    = 0;
        $totalValueShort   = 0;
        $totalValueOption  = 0;

        foreach ($transactions as $transaction) {
            $transactionValue   = $transaction->getQuantity() *$transaction->getPrice();
            $totalCash          += $transactionValue;
        }

        foreach ($stocks as $stock) {
            $profitLong         = $stock->getSoldProfit('Long');
            $profitShort        = $stock->getSoldProfit('Short');
            $profitOption       = $stock->getSoldProfit('Option');

            dump('symbol: '.$stock->getSymbol().', long: '.$profitLong.', short: '.$profitShort.', option: '.$profitOption);

            $totalCash          += $profitLong +$profitShort +$profitOption;

            $valueLong          = $stock->getPrice() *$stock->getQuantity('Long');
            $valueShort         = $stock->getPrice() *$stock->getQuantity('Short');
            $valueOption        = $stock->getPrice() *$stock->getQuantity('Option');

            $totalValueLong    += $valueLong;
            $totalValueShort   += $valueShort;
            $totalValueOption  += $valueOption;
        }

        return $totalCash +$totalValueLong +$totalValueOption -$totalValueShort;
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

    public function createOrGetStock($record, $symbol) {
        $stockType = $record->getTradeType() == 'Option' ? 'Option' : 'Stock';

        $stock = $this->em->getRepository('App:Stock')->findOneBy([
            'symbol' => $symbol
        ]);

        if (!$stock) {
            $stock = new Stock();
            $stock->setSymbol($symbol);
            $stock->setStockType($stockType);
            $stock->setWatchlistLong(false);
            $stock->setWatchlistShort(false);
            $this->em->persist($stock);
            $this->em->flush();
        }

        return $stock;
    }

    public function getTradeType($description) {
        if (strpos($description, 'Stock: Buy') !== false) {
            return 'Long';
        } elseif (strpos($description, 'Stock: Sell') !== false) {
            return 'Long';
        } elseif (strpos($description, 'Short Stock: Short') !== false) {
            return 'Short';
        } elseif (strpos($description, 'Cover Stock: Cover') !== false) {
            return 'Short';
        } elseif (strpos($description, 'Option: Buy') !== false) {
            return 'Option';
        } elseif (strpos($description, 'Option: Sell') !== false) {
            return 'Option';
        } elseif (strpos($description, 'Option Expired') !== false) {
            return 'Option';
        } elseif (strpos($description, 'Dividend') !== false) {
            return 'Dividend';
        } elseif (strpos($description, 'Interest') !== false) {
            return 'Interest';
        }

        return '-';
    }

    public function getOrderType($description) {
        if (strpos($description, 'Stock: Buy') !== false) {
            return 'Buy';
        } elseif (strpos($description, 'Stock: Sell') !== false) {
            return 'Sell';
        } elseif (strpos($description, 'Short Stock: Short') !== false) {
            return 'Buy';
        } elseif (strpos($description, 'Cover Stock: Cover') !== false) {
            return 'Sell';
        } elseif (strpos($description, 'Option: Buy') !== false) {
            return 'Buy';
        } elseif (strpos($description, 'Option: Sell') !== false) {
            return 'Sell';
        } elseif (strpos($description, 'Option Expired') !== false) {
            return 'Expired';
        }

        return '-';
    }

    public function getTradeAdjustedPrice(Trade $trade) {
        $historicalRelatedTrades = $this->em->getRepository('App:Trade')->findHistoricalRelatedTrades($trade);

        $totalValue             = 0;
        $totalQuantity          = 0;
        $weightedAveragePrice   = 0;

        foreach ($historicalRelatedTrades as $hTrade) {
            // LONG POSITIONS
            if ($hTrade->getTradeType() == "Long" || $hTrade->getTradeType() == "Call") {
                if ($hTrade->getOrderType() == "Sell") {
                    $totalQuantity -= $hTrade->getQuantity();
                    $totalValue = $totalQuantity *$weightedAveragePrice;
                }

                if ($totalQuantity == 0) {
                    $totalValue = 0;
                }

                if ($hTrade->getOrderType() == "Buy") {
                    $totalQuantity += $hTrade->getQuantity();
                    $totalValue += ($hTrade->getQuantity() *$hTrade->getPrice());

                    $weightedAveragePrice = ($totalQuantity > 0) ? $totalValue / $totalQuantity : 0;
                }
            }

            // SHORT POSITIONS
            if ($hTrade->getTradeType() == "Short" || $hTrade->getTradeType() == "Put") {
                if ($hTrade->getOrderType() == "Sell") {
                    $totalQuantity -= $hTrade->getQuantity();
                    $totalValue = $totalQuantity *$weightedAveragePrice;
                }

                if ($totalQuantity == 0) {
                    $totalValue = 0;
                }

                if ($hTrade->getOrderType() == "Buy") {
                    $totalQuantity += $hTrade->getQuantity();
                    $totalValue += ($hTrade->getQuantity() *$hTrade->getPrice());

                    $weightedAveragePrice = ($totalQuantity > 0) ? $totalValue / $totalQuantity : 0;
                }
            }
        }

        return $weightedAveragePrice;
    }

}

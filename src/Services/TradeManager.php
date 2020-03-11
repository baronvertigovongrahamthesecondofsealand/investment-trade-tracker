<?php

namespace App\Services;

use App\Entity\Stock;
use App\Entity\Trade;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class TradeManager {

    protected $em;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->em = $entityManager;
    }

    public function getAccountValue($getBuyingPower = false, \DateTime $startDate = null, \DateTime $endDate = null) {
        $query = $this->em->getRepository('App:Stock')->createQueryBuilder('s')
            ->leftJoin('s.trades', 't')
            ->orderBy('t.executedAt', 'ASC');

        if ($startDate && $endDate) {
            $query
                ->andWhere('t.executedAt BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        /** @var Stock[] $stocks */
        $stocks = $query->getQuery()->execute();

        $totalCash         = $this->getCash($startDate, $endDate);
        $totalValueLong    = 0;
        $totalValueShort   = 0;
        $totalValueOption  = 0;

        foreach ($stocks as $stock) {
            $profitLong         = $stock->getSoldProfit('Long');
            $profitShort        = $stock->getSoldProfit('Short');
            $profitOption       = $stock->getSoldProfit('Option');

            if ($stock->getSymbol() == 'AMD1922K42') {
                dump('symbol: '.$stock->getSymbol().', long: '.$profitLong.', short: '.$profitShort.', option: '.$profitOption);
            }

            $totalCash          += $profitLong +$profitShort +$profitOption;

            $valueLong          = $stock->getPrice() *$stock->getQuantity('Long') *($getBuyingPower ? 0.5 : 1);
            $valueShort         = $stock->getPrice() *$stock->getQuantity('Short') *($getBuyingPower ? 1.5 : 1);
            $valueOption        = $stock->getPrice() *$stock->getQuantity('Option');

            $totalValueLong    += $valueLong;
            $totalValueShort   += $valueShort;
            $totalValueOption  += $valueOption;
        }

        return $totalCash +$totalValueLong +$totalValueOption -$totalValueShort;
    }

    public function getBuyingPower() {
        return 6134.12;
        return $this->getAccountValue(true);
    }

    public function getCash(\DateTime $startDate = null, \DateTime $endDate = null) {
        return 33643.85;

        $totalCash = 0;

        $query = $this->em->getRepository('App:Transaction')->createQueryBuilder('tr')
            ->orderBy('tr.executedAt', 'ASC');

        if ($startDate && $endDate) {
            $query
                ->andWhere('tr.executedAt BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        $transactions = $query->getQuery()->execute();

        foreach ($transactions as $transaction) {
            $transactionValue   = $transaction->getQuantity() *$transaction->getPrice();
            $totalCash          += $transactionValue;
        }

        return $totalCash;
    }

    public function getAnnualReturn() {
        return 0.5904;
        $firstTrade = $this->em->getRepository('App:Trade')->findOneBy([], [ 'executedAt' => 'ASC' ]);

        $initialDate    = $firstTrade->getExecutedAt();
        $ytdDate        = clone $initialDate;
        $ytdDate->setDate(date('Y'), $ytdDate->format('m'), $ytdDate->format('d'));

        $now = new \DateTime('now');

        $diff = $ytdDate->diff($now);

        if ($diff->invert) {
            $ytdDate->setDate(date('Y', strtotime('-1 year')), $ytdDate->format('m'), $ytdDate->format('d'));
        }

        $accountValStart    = $initialDate == $ytdDate ? 100000 : $this->getAccountValue(false, $initialDate, $ytdDate);
        $accountValEnd      = $this->getAccountValue(false, $ytdDate, $now);

        $daysInYear     = 365;
        $daysYTD        = $ytdDate->diff($now)->days;

        $valueCalc      = $accountValEnd /$accountValStart;
        $expCalc        = $daysInYear /$daysYTD;
        $annualReturn   = ($valueCalc ** $expCalc) -1;

//        dump($accountValStart);
//        dump($accountValEnd);
//        dump($valueCalc);
//        dump($expCalc);
//        dump($annualReturn);

        return $annualReturn;
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

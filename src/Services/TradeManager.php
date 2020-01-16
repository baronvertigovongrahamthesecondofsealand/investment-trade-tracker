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

    public function createOrGetStock($trade, $symbol) {
        $stockType = $this->getTradeType($trade) == 'Call' ? 'Option' : 'Stock';

        $stock = $this->em->getRepository('App:Stock')->findOneBy([
            'symbol' => $symbol
        ]);

        if (!$stock) {
            $stock = new Stock();
            $stock->setSymbol($symbol);
            $stock->setStockType($stockType);
            $this->em->persist($stock);
            $this->em->flush();
        }

        return $stock;
    }

    public function getTradeType(Trade $trade) {
        if (strpos($trade->getDescription(), 'Stock: Buy') !== false) {
            return 'Long';
        } elseif (strpos($trade->getDescription(), 'Stock: Sell') !== false) {
            return 'Long';
        } elseif (strpos($trade->getDescription(), 'Short Stock: Short') !== false) {
            return 'Short';
        } elseif (strpos($trade->getDescription(), 'Cover Stock: Cover') !== false) {
            return 'Short';
        } elseif (strpos($trade->getDescription(), 'Option: Buy') !== false) {
            return 'Call';
        } elseif (strpos($trade->getDescription(), 'Option: Sell') !== false) {
            return 'Call';
        } elseif (strpos($trade->getDescription(), 'Option Expired') !== false) {
            return 'Call';
        }

        return '-';
    }

    public function getOrderType(Trade $trade) {
        if (strpos($trade->getDescription(), 'Stock: Buy') !== false) {
            return 'Buy';
        } elseif (strpos($trade->getDescription(), 'Stock: Sell') !== false) {
            return 'Sell';
        } elseif (strpos($trade->getDescription(), 'Short Stock: Short') !== false) {
            return 'Buy';
        } elseif (strpos($trade->getDescription(), 'Cover Stock: Cover') !== false) {
            return 'Sell';
        } elseif (strpos($trade->getDescription(), 'Option: Buy') !== false) {
            return 'Buy';
        } elseif (strpos($trade->getDescription(), 'Option: Sell') !== false) {
            return 'Sell';
        } elseif (strpos($trade->getDescription(), 'Option Expired') !== false) {
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

//            dump($hTrade->getId().": { totalQuantity: ".$totalQuantity.", price: ".$hTrade->getPrice().", totalValue: ".$totalValue.", weightedAveragePrice: ".$weightedAveragePrice." }");
        }

        return $weightedAveragePrice;
    }

}
<?php

namespace App\Twig;

use App\Entity\Stock;
use App\Services\TradeManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Extension extends AbstractExtension {

    protected $tradeManager;

    public function __construct(TradeManager $tradeManager) {
        $this->tradeManager = $tradeManager;
    }

    public function getFilters() {
        return [
            new TwigFilter('timeAgo', [ $this, 'timeAgo' ])
        ];
    }
    public function getFunctions() {
        return [
            new TwigFunction('calcAdjustedPrice', [ $this, 'calcAdjustedPrice' ])
        ];
    }

    public function calcAdjustedPrice(Stock $stock, $tradeType) {
        $lastTrade = $stock->getTrades($tradeType, ['executedAt', 'DESC'])->first();

        return $this->tradeManager->getTradeAdjustedPrice($lastTrade);
    }

    public function timeAgo(\DateTime $datetime) {
        $date_dir   = (time() < $datetime->format('U'));

        $diff       = $date_dir
                    ? $datetime->format('U') -time()
                    : time() -$datetime->format('U');

        $max_diff   = 604800;

        $units = [
            'year'      => 31536000,
            'month'     => 2592000,
            'week'      => 604800,
            'day'       => 86400,
//            'hour'      => 3600,
//            'minute'    => 60,
            'second'    => 1,
        ];

        foreach ($units as $val => $unit) {
            if ($diff < $unit) {
                continue;
            }
            if ($diff >= $max_diff) {
                continue;
            }

            $numberOfUnits = floor($diff /$unit);

            $stringPrefix   = 'in';
            $unitSuffix     = ($numberOfUnits > 1) ? 's' : '';
            $stringSuffix   = 'ago';

            if (!$date_dir) {
                $stringPrefix = '';
            } else {
                $stringSuffix = '';
            }

            if ($val == 'second') {
                return 'Today';
            } else {
                return $stringPrefix.' '.$numberOfUnits.' '.$val.$unitSuffix.' '.$stringSuffix;
            }
        }

        return $datetime->format('jS M \'y');
    }

}
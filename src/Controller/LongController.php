<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Services\TradeManager;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class LongController extends BaseController {

    public $tradeType       = 'Long';
    public $viewRoute       = 'long_main';
    public $detailRoute     = 'long_detail';

    public function detail(Request $request, TradeManager $tradeManager) {
        $this->init($request);

        $this->detailFormBuilder = $this->getDetailForm([
            'longTarget' => NumberType::class
        ]);

        return parent::detail($request, $tradeManager);
    }

}
<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Services\TradeManager;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class OptionController extends BaseController {

    public $tradeType       = 'Option';
    public $viewRoute       = 'option_main';
    public $detailRoute     = 'option_detail';

    public function detail(Request $request, TradeManager $tradeManager) {
        $this->init($request);

        $this->detailFormBuilder = $this->getDetailForm([
            'callTarget' => NumberType::class,
            'price' => NumberType::class,
        ]);

        return parent::detail($request, $tradeManager);
    }

}
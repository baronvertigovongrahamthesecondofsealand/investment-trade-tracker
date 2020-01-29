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
    public $viewTemplate    = 'long/view.html.twig';
    public $detailRoute     = 'option_detail';

    public function detail(Request $request, TradeManager $tradeManager) {
        $this->detailFormBuilder = $this->createFormBuilder(new Stock());
        $this->detailFormBuilder
            ->add('callTarget', NumberType::class)
            ->add('price', NumberType::class)
            ->add('nextEarningsAt', DateType::class, [
                'data' => new \DateTime()
            ])
            ->add('submit', SubmitType::class);

        return parent::detail($request, $tradeManager);
    }

}
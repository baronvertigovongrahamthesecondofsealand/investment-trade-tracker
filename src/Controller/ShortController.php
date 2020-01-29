<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Services\TradeManager;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class ShortController extends BaseController {

    public $tradeType       = 'Short';
    public $viewRoute       = 'short_main';
    public $viewTemplate    = 'short/view.html.twig';
    public $detailRoute     = 'short_detail';

    public function detail(Request $request, TradeManager $tradeManager) {
        $this->detailFormBuilder = $this->createFormBuilder(new Stock());
        $this->detailFormBuilder
            ->add('shortTarget', NumberType::class)
            ->add('nextEarningsAt', DateType::class, [
                'data' => new \DateTime()
            ])
            ->add('submit', SubmitType::class);

        return parent::detail($request, $tradeManager);
    }

}
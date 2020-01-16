<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Entity\Trade;
use App\Services\TradeManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class DashboardController extends AbstractController {

    public function view(Request $request, TradeManager $tradeManager) {
        $em = $this->getDoctrine()->getManager();

        $accountValue   = $em->getRepository('App:Stock')->getAccountValue();
        $buyingpower    = $em->getRepository('App:Stock')->getBuyingPower();
        $cash           = $em->getRepository('App:Stock')->getCash();
        $annualReturn   = $em->getRepository('App:Stock')->getAnnualReturn();

        return $this->render('dashboard/view.html.twig', [
            'accountValue'  => $accountValue,
            'buyingpower'   => $buyingpower,
            'cash'          => $cash,
            'annualReturn'  => $annualReturn
        ]);
    }

}
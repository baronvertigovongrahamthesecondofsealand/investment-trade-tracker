<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Entity\Trade;
use App\Services\TradeManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class CallController extends AbstractController {

    public function view(Request $request, TradeManager $tradeManager) {
        $em = $this->getDoctrine()->getManager();

        $stocks = $em->getRepository('App:Stock')->createQueryBuilder('s')
            ->leftJoin('s.trades', 't')
            ->andWhere('t.trade_type = :tradetype')
            ->addOrderBy('t.executedAt', 'ASC')
            ->getQuery()
            ->execute([
                'tradetype' => 'Call'
            ]);

//        /** @var Stock[] $stocks */
//        foreach ($stocks as $stock) {
//            foreach ($stock->getTrades() as $trade) {
//                $trade->setAdjustedPrice($tradeManager->getTradeAdjustedPrice($trade));
//                $em->persist($trade);
//            }
//        }
//
//        $em->flush();

        return $this->render('call/view.html.twig', [
            'stocks' => $stocks
        ]);
    }

}
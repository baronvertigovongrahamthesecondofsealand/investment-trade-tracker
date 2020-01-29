<?php

namespace App\Controller;

use App\Services\TradeManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BaseController extends AbstractController {

    public $tradeType           = 'Default';
    public $viewRoute           = 'dashboard';
    public $viewTemplate        = 'default/view.html.twig';
    public $detailRoute         = 'dashboard';
    public $detailTemplate      = 'default/detail.html.twig';
    public $detailFormBuilder   = null;

    public function view(Request $request, TradeManager $tradeManager) {
        $em = $this->getDoctrine()->getManager();

        $stocks = $em->getRepository('App:Stock')->createQueryBuilder('s')
            ->leftJoin('s.trades', 't')
            ->andWhere('t.trade_type = :tradetype')
            ->addOrderBy('t.executedAt', 'ASC')
            ->getQuery()
            ->execute([
                'tradetype' => $this->tradeType
            ]);

        return $this->render($this->viewTemplate, [
            'detailRoute' => $this->detailRoute,
            'tradeType' => $this->tradeType,
            'stocks' => $stocks
        ]);
    }

    public function detail(Request $request, TradeManager $tradeManager) {
        $em = $this->getDoctrine()->getManager();

        $symbol = $request->attributes->get('symbol');

        $stock = $em->getRepository('App:Stock')->findOneBy([
            'symbol' => $symbol
        ]);

        if (!$stock) {
            throw new NotFoundHttpException('Could not find the symbol '.$symbol);
        }

        $this->detailFormBuilder->setData($stock);

        $form = $this->detailFormBuilder->getForm();

        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $stock = $form->getData();

                $em->persist($stock);
                $em->flush();

                return $this->redirectToRoute($this->viewRoute);
            }
        }

        return $this->render('default/detail.html.twig', [
            'tradeType' => $this->tradeType,
            'stock' => $stock,
            'symbol' => $symbol,
            'form' => $form->createView()
        ]);
    }

}
<?php

namespace App\Controller;

use App\Entity\Trade;
use App\Services\TradeManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class HistoryController extends AbstractController {

    public function view(Request $request, TradeManager $tradeManager) {
        $em = $this->getDoctrine()->getManager();

        $trades = $em->getRepository('App:Trade')->createQueryBuilder('t')
            ->leftJoin('t.stock', 's')
            ->addOrderBy('t.executedAt', 'ASC')
            ->addOrderBy('s.symbol', 'ASC')
            ->addOrderBy('t.quantity', 'ASC')
            ->getQuery()
            ->execute();

        return $this->render('history/view.html.twig', [
            'trades' => $trades
        ]);
    }

    public function addRawTrades(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $tradeRepo = $em->getRepository('App:Trade');

        $builder = $this->createFormBuilder();
        $builder
            ->add('pastebox', TextareaType::class)
            ->add('submit', SubmitType::class);

        $form = $builder->getForm();

        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $raw_data = $form->getData()['pastebox'];

                $trades = $tradeRepo->createFromRaw($raw_data);

                foreach ($trades as $trade) {
                    $em->persist($trade);
                }

                $em->flush();

                return $this->redirectToRoute('history_main');
            }
        }

        return $this->render('history/addRawTrades.html.twig', [
            'form' => $form->createView()
        ]);
    }

}
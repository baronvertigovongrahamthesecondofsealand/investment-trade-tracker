<?php

namespace App\Controller;

use App\Services\TradeManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class TransactionController extends AbstractController {

    public function view(Request $request, TradeManager $tradeManager) {
        $em = $this->getDoctrine()->getManager();

        $transactions = $em->getRepository('App:Transaction')->getAll();

        return $this->render('transaction/view.html.twig', [
            'transactions' => $transactions
        ]);
    }

    public function add(Request $request, TradeManager $tradeManager) {
        $em = $this->getDoctrine()->getManager();
        $transactionRepo = $em->getRepository('App:Transaction');

        $builder = $this->createFormBuilder();
        $builder
            ->add('pastebox', TextareaType::class)
            ->add('submit', SubmitType::class);

        $form = $builder->getForm();

        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $raw_data = $form->getData()['pastebox'];

                $records = $transactionRepo->createFromRaw($raw_data);

                foreach ($records as $record) {
                    $em->persist($record);
                }

                $em->flush();

                return $this->redirectToRoute('transaction_main');
            }
        }

        $lastTransactionAdded = $transactionRepo->findOneBy([], [
            'executedAt' => 'DESC'
        ]);

        return $this->render('transaction/add.html.twig', [
            'form' => $form->createView(),
            'lastTransactionAdded' => $lastTransactionAdded
        ]);
    }

}

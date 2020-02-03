<?php

namespace App\Controller;

use App\Services\TradeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BaseController extends AbstractController {

    /** @var EntityManagerInterface */
    public $em                  = null;
    public $tradeType           = 'Default';
    public $viewRoute           = 'dashboard';
    public $viewTemplate        = 'default/view.html.twig';
    public $detailRoute         = 'dashboard';
    public $detailTemplate      = 'default/detail.html.twig';

    /** @var FormBuilderInterface */
    public $detailFormBuilder   = null;
    public $symbol              = null;
    public $stock               = null;

    public function init(Request $request) {
        $this->em       = $this->getDoctrine()->getManager();
        $this->symbol   = $request->attributes->get('symbol');

        if ($this->symbol) {
            $this->stock    = $this->em->getRepository('App:Stock')->findOneBy([
                'symbol' => $this->symbol
            ]);

            if (!$this->stock) {
                throw new NotFoundHttpException('Could not find the symbol '.$this->symbol);
            }
        } else {
            $this->stock = null;
        }
    }

    public function view(Request $request, TradeManager $tradeManager) {
        $this->init($request);

        $stocks = $this->em->getRepository('App:Stock')->createQueryBuilder('s')
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

    public function preDetail() {

    }

    public function detail(Request $request, TradeManager $tradeManager) {
        $this->init($request);

        $form = $this->detailFormBuilder->getForm();

        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->stock = $form->getData();

                $this->em->persist($this->stock);
                $this->em->flush();

                return $this->redirectToRoute($this->viewRoute);
            }
        }

        return $this->render('default/detail.html.twig', [
            'tradeType' => $this->tradeType,
            'stock' => $this->stock,
            'symbol' => $this->symbol,
            'form' => $form->createView()
        ]);
    }

    public function getDetailForm($extraFields) {
        $formB = $this->createFormBuilder($this->stock);

        foreach ($extraFields as $extraFieldName => $extraFieldValue) {
            $formB->add($extraFieldName, $extraFieldValue);
        }

        $formB
            ->add('nextEarningsAt', DateType::class, $this->stock ? [] : [
                'data' => new \DateTime()
            ])
            ->add('submit', SubmitType::class);

        return $formB;

    }

}
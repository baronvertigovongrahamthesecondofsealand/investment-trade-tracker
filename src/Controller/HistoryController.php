<?php

namespace App\Controller;

use App\Services\TradeManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HistoryController extends AbstractController {

    public function view(Request $request, TradeManager $tradeManager) {
        $em = $this->getDoctrine()->getManager();

        $trades = $em->getRepository('App:Trade')->getAllTrades();

        return $this->render('history/view.html.twig', [
            'trades' => $trades
        ]);
    }

    public function changeTradeType(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $trade = $em->getRepository('App:Trade')->find($request->attributes->get('tradeId'));

        if (!$trade) {
            throw new NotFoundHttpException('Could not find the given trade');
        }

        $newTradeType = $request->query->get('tradeType');

        $trade->setTradeType($newTradeType);
        $em->persist($trade);
        $em->flush();

        return $this->redirectToRoute('history_main');
    }

    public function changeOrderType(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $trade = $em->getRepository('App:Trade')->find($request->attributes->get('tradeId'));

        if (!$trade) {
            throw new NotFoundHttpException('Could not find the given trade');
        }

        $newOrderType = $request->query->get('orderType');

        $trade->setOrderType($newOrderType);
        $em->persist($trade);
        $em->flush();

        return $this->redirectToRoute('history_main');
    }

    public function add(Request $request, TradeManager $tradeManager) {
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

                foreach ($trades as $trade) {
                    $em->refresh($trade);
                }

                foreach ($trades as $trade) {
                    $trade->setAdjustedPrice($tradeManager->getTradeAdjustedPrice($trade));
                    $em->persist($trade);
                }

                $em->flush();

                return $this->redirectToRoute('history_main');
            }
        }

        $lastTradeAdded = $tradeRepo->findOneBy([], [
            'executedAt' => 'DESC'
        ]);

        return $this->render('history/add.html.twig', [
            'form' => $form->createView(),
            'lastTradeAdded' => $lastTradeAdded
        ]);
    }

}


//1/28/2020 10:01 AM 	Stock: Buy at Market Open 	NVDA 	5 		$242.85 	$14.99 	$1,229.24 	$140,945.63
//1/27/2020 12:54 PM 	Option: Buy to Open at Market 	AMD2028B50 (AMD 50 - Fri 2020) 	5 		$3.20 	$23.74 	$1,623.74 	$139,722.02
//1/27/2020 12:50 PM 	Stock: Buy at Market 	GDP 	600 		$7.05 	$14.99 	$4,244.27 	$139,477.82
//1/27/2020 12:28 PM 	Stock: Sell at Market 	ONCY 	2399 		$3.25 	$14.99 	$7,781.76 	$138,559.14
//1/27/2020 12:27 PM 	Stock: Sell at Market 	AJRD 	199 		$52.43 	$14.99 	$10,418.58 	$137,373.17
//1/24/2020 12:42 PM 	Option: Sell to Close at Market 	INTC2024A63 (INTC 63 - Fri 2020) 	1 		$5.40 	$16.74 	$523.26 	$149,516.92
//1/23/2020 12:27 PM 	Option: Buy to Open at Market 	INTC2031M57.5 (INTC 57.5 - Fri 2020) 	25 		$0.20 	$58.74 	$558.74 	$160,876.12
//1/22/2020 1:51 PM 	Stock: Sell at Market 	NVDA 	9 		$251.97 	$14.99 	$2,252.70 	$171,630.86
//1/22/2020 1:51 PM 	Option: Buy to Open at Market 	INTC2024A63 (INTC 63 - Fri 2020) 	1 		$1.17 	$16.74 	$133.74 	$171,649.60
//1/22/2020 12:54 PM 	Option: Buy to Open at Market 	INTC2031M57.5 (INTC 57.5 - Fri 2020) 	15 		$0.29 	$41.24 	$476.24 	$170,380.03
//1/21/2020 1:45 PM 	Option: Buy to Open at Market 	PLUG2021N3.5 (PLUG 3.5 - Fri 2020) 	5 		$0.08 	$23.74 	$63.74 	$163,748.27
//1/16/2020 9:57 AM 	Option: Buy to Open at Market Open 	INTC2031M57.5 (INTC 57.5 - Fri 2020) 	10 		$0.90 	$32.49 	$932.49 	$156,387.94
//1/15/2020 1:23 PM 	Stock: Buy at Market 	TSLA 	20 		$531.81 	$14.99 	$10,651.19 	$154,829.47
//1/15/2020 12:18 PM 	Option: Buy to Open at Market 	PLUG2031M3 (PLUG 3 - Fri 2020) 	10 		$0.03 	$32.49 	$62.49 	$155,840.35
//1/14/2020 3:56 PM 	Stock: Buy at Market 	AMD 	500 		$48.25 	$14.99 	$24,139.99 	$153,180.17
//1/14/2020 3:27 PM 	Stock: Buy at Market 	CRSP 	50 		$59.86 	$14.99 	$3,007.99 	$153,798.09
//1/14/2020 3:16 PM 	Stock: Buy at Market 	ONCY 	2000 		$2.96 	$14.99 	$5,933.19 	$153,762.91
//1/14/2020 3:07 PM 	Stock: Buy at Market 	GDP 	1000 		$8.77 	$14.99 	$8,784.99 	$154,904.84
//1/14/2020 2:18 PM 	Option: Buy to Open at Market 	TSLA2014B550 (TSLA 550 - Fri 2020) 	1 		$40.90 	$16.74 	$4,106.74 	$154,093.07
//1/14/2020 2:15 PM 	Stock: Buy at Market 	TSLA 	10 		$540.15 	$14.99 	$5,416.49 	$154,363.66
//1/13/2020 3:46 PM 	Stock: Buy at Market 	AAPL 	100 		$315.13 	$14.99 	$31,527.99 	$154,299.38
//1/13/2020 2:15 PM 	Option: Buy to Open at Market 	AMD2024A50 (AMD 50 - Fri 2020) 	15 		$0.76 	$41.24 	$1,181.24 	$154,077.75
//1/13/2020 2:14 PM 	Stock: Buy at Market 	KTOS 	300 		$19.85 	$14.99 	$5,969.99 	$154,092.74
//1/13/2020 2:10 PM 	Stock: Buy at Market 	ONCY 	200 		$3.49 	$14.99 	$712.99 	$154,169.89
//1/13/2020 12:09 PM 	Option: Sell to Close at Market 	TSLA2017A505 	5 		$14.70 	$23.74 	$7,326.26 	$153,563.65
//1/13/2020 9:57 AM 	Option: Sell to Close at Market Open 	AAPL0101A302.5 (AAPL 302.5 - Mon 0001) 	6 		$10.45 	$25.49 	$6,244.51 	$152,821.24
//1/13/2020 9:57 AM 	Option: Buy to Open at Market Open 	AMD2024A50 (AMD 50 - Fri 2020) 	15 		$0.90 	$41.24 	$1,391.24 	$153,012.48
//1/9/2020 3:19 PM 	Stock: Sell at Market 	ONCY 	4800 		$4.00 	$14.99 	$19,185.01 	$146,688.66
//1/9/2020 10:27 AM 	Short Stock: Short at Market Open 	TAST 	1000 		$6.68 	$14.99 	$6,665.01 	$146,911.46
//1/9/2020 9:59 AM 	Stock: Buy at Market Open 	VSTM 	5000 		$1.81 	$14.99 	$9,064.99 	$148,231.94
//1/9/2020 9:59 AM 	Short Stock: Short at Market Open 	BA 	10 		$334.95 	$14.99 	$3,334.51 	$148,230.23
//1/9/2020 9:58 AM 	Short Stock: Short at Market Open 	ITCI 	250 		$26.81 	$14.99 	$6,687.51 	$148,034.22
//1/9/2020 9:58 AM 	Stock: Buy at Market Open 	APA 	200 		$32.61 	$14.99 	$6,536.99 	$148,063.21
//1/9/2020 9:58 AM 	Stock: Buy at Market Open 	ONCY 	5000 		$3.90 	$14.99 	$19,514.99 	$147,628.20
//1/8/2020 1:56 PM 	Short Stock: Short at Market 	TVIX 	500 		$46.65 	$14.99 	$23,307.91 	$142,133.49
//1/8/2020 1:54 PM 	Option: Buy to Open at Market 	TSLA2017A505 	5 		$11.00 	$23.74 	$5,523.74 	$142,180.86
//1/8/2020 12:18 PM 	Stock: Buy at Market 	AMD 	100 		$47.56 	$14.99 	$4,770.51 	$141,544.46
//1/8/2020 12:18 PM 	Option: Buy to Open at Market 	AMD2024A50 (AMD 50 - Fri 2020) 	10 		$0.84 	$32.49 	$872.49 	$141,616.95
//1/8/2020 12:18 PM 	Option: Buy to Open at Market 	AAPL0101A302.5 (AAPL 302.5 - Mon 0001) 	3 		$3.45 	$20.24 	$1,055.24 	$141,652.19
//1/7/2020 3:39 PM 	Option: Buy to Open at Market 	AAPL0101A302.5 (AAPL 302.5 - Mon 0001) 	1 		$3.45 	$16.74 	$361.74 	$141,044.76
//1/7/2020 2:01 PM 	Short Stock: Short at Market 	TVIX 	400 		$49.37 	$14.99 	$19,731.01 	$141,129.64
//1/7/2020 2:00 PM 	Option: Buy to Open at Market 	AAPL0101A302.5 (AAPL 302.5 - Mon 0001) 	1 		$3.65 	$16.74 	$381.74 	$141,151.38
//1/7/2020 1:56 PM 	Stock: Sell at Market 	LMT 	9 		$414.27 	$14.99 	$3,713.42 	$141,141.37
//1/7/2020 10:42 AM 	Stock: Sell at Market Open 	PLUG 	499 		$3.76 	$14.99 	$1,861.25 	$141,456.23
//1/7/2020 9:58 AM 	Stock: Buy at Market Open 	AAPL 	50 		$299.84 	$14.99 	$15,006.99 	$142,234.25
//1/7/2020 9:57 AM 	Option: Buy to Open at Market Open 	AAPL0101A302.5 (AAPL 302.5 - Mon 0001) 	1 		$4.00 	$16.74 	$416.74 	$142,225.99
//1/6/2020 3:42 PM 	Stock: Buy at Market 	OXY 	150 		$45.23 	$14.99 	$6,799.49 	$141,022.18
//1/6/2020 10:15 AM 	Option: Buy to Open at Market Open 	AMD2024A50 (AMD 50 - Fri 2020) 	20 		$1.30 	$49.99 	$2,649.99 	$140,583.22
//1/6/2020 10:03 AM 	Stock: Buy at Market Open 	AMD 	400 		$48.02 	$14.99 	$19,222.99 	$140,347.72
//1/6/2020 10:03 AM 	Stock: Buy at Market Open 	KTOS 	200 		$21.30 	$14.99 	$4,274.99 	$140,424.71
//1/6/2020 10:03 AM 	Short Stock: Short at Market Open 	TVIX 	100 		$54.98 	$14.99 	$5,483.01 	$140,332.70
//1/3/2020 12:39 PM 	Option: Sell to Close at Market 	LMT2017A400 (LMT 400 - Fri 2020) 	20 		$17.00 	$49.99 	$33,950.01 	$140,200.23
//1/3/2020 12:39 PM 	Stock: Sell at Market 	LMT 	30 		$414.54 	$14.99 	$12,421.21 	$140,215.22
//12/27/2019 9:57 AM 	Option: Buy to Open at Market Open 	LMT2017A400 (LMT 400 - Fri 2020) 	17 		$2.45 	$44.74 	$4,209.74 	$109,029.57
//12/26/2019 12:37 PM 	Stock: Buy at Market 	NVDA 	10 		$238.34 	$14.99 	$2,398.41 	$108,818.66
//12/26/2019 12:27 PM 	Option: Sell to Close at Market 	AMD0101A42 (AMD 42 - Mon 0001) 	10 		$4.60 	$32.49 	$4,567.51 	$108,879.29
//12/26/2019 12:26 PM 	Stock: Sell at Market 	AMD 	299 		$46.55 	$14.99 	$13,902.05 	$108,994.28
//12/26/2019 12:18 PM 	Stock: Sell at Market 	INTC 	149 		$59.74 	$14.99 	$8,886.27 	$109,377.07
//12/26/2019 12:13 PM 	Stock: Sell at Market 	AMZN 	9 		$1,845.56 	$14.99 	$16,595.05 	$109,443.40
//12/26/2019 9:57 AM 	Option: Buy to Open at Market Open 	LMT2017A400 (LMT 400 - Fri 2020) 	2 		$2.55 	$18.49 	$528.49 	$109,685.93
//12/23/2019 2:02 PM 	Stock: Buy at Market 	PLUG 	450 		$2.97 	$14.99 	$1,349.24 	$107,712.97
//12/23/2019 1:02 PM 	Cover Stock: Cover at Market 	QQQX 	500 		$23.92 	$14.99 	$11,974.99 	$107,705.59
//12/23/2019 1:00 PM 	Option: Sell to Close at Market 	AMD0101A42 (AMD 42 - Mon 0001) 	40 		$4.05 	$84.99 	$16,115.01 	$107,740.58
//12/23/2019 12:58 PM 	Stock: Sell at Market 	AMD 	400 		$45.46 	$14.99 	$18,169.01 	$107,760.57
//12/23/2019 10:02 AM 	Stock: Buy at Market Open 	LMT 	20 		$387.66 	$14.99 	$7,768.19 	$104,247.56
//12/20/2019 12:52 PM 	Stock: Sell at Market 	AMZN 	5 		$1,797.59 	$14.99 	$8,972.96 	$99,118.11
//12/16/2019 1:36 PM 	Stock: Sell at Market 	AMD 	300 		$42.48 	$14.99 	$12,729.01 	$94,915.78
//12/11/2019 2:01 PM 	Stock: Buy at Market 	LMT 	20 		$385.79 	$14.99 	$7,730.69 	$84,890.71
//12/5/2019 12:09 PM 	Cover Stock: Cover at Market 	INTC 	100 		$55.95 	$14.99 	$5,609.99 	$88,334.30
//12/3/2019 2:24 PM 	Cover Stock: Cover at Market 	INTC 	500 		$56.02 	$14.99 	$28,024.99 	$84,651.64
//11/27/2019 3:28 PM 	Stock: Sell at Market 	CRSP 	19 		$68.10 	$14.99 	$1,278.91 	$88,233.39
//11/26/2019 3:39 PM 	Stock: Sell at Market 	PLUG 	450 		$3.89 	$14.99 	$1,733.26 	$86,852.90
//11/25/2019 3:38 PM 	Stock: Sell at Market 	INTC 	150 		$58.73 	$14.99 	$8,794.51 	$88,753.54
//11/25/2019 1:21 PM 	Option: Buy to Open at Market 	AMD0101A42 (AMD 42 - Mon 0001) 	40 		$1.68 	$84.99 	$6,804.99 	$89,770.73
//11/25/2019 10:03 AM 	Option: Buy to Open at Market Open 	AMD0101A42 (AMD 42 - Mon 0001) 	10 		$1.61 	$32.49 	$1,642.49 	$89,192.40
//11/25/2019 9:57 AM 	Option: Buy to Open at Market Open 	LMT2017A400 (LMT 400 - Fri 2020) 	1 		$4.10 	$16.74 	$426.74 	$89,169.14
//11/22/2019 9:59 AM 	Stock: Sell at Market Open 	LMT 	25 		$390.19 	$14.99 	$9,739.76 	$88,085.93
//11/21/2019 1:51 PM 	Option: Buy to Open at Market 	AMD1922K42 	600 		$0.04 	$1,064.99 	$3,464.99 	$91,375.20
//11/20/2019 2:51 PM 	Stock: Buy at Market 	PLUG 	500 		$3.49 	$14.99 	$1,757.74 	$107,511.77
//11/20/2019 1:53 PM 	Stock: Sell at Market 	LMT 	25 		$392.04 	$14.99 	$9,785.89 	$109,425.08
//11/20/2019 1:42 PM 	Option: Buy to Open at Market 	AMD1922K42.5 	300 		$0.13 	$539.99 	$4,439.99 	$104,441.54
//11/19/2019 3:24 PM 	Option: Buy to Open at Market 	AMD1922K42.5 	300 		$0.37 	$539.99 	$11,639.99 	$124,142.63
//11/19/2019 1:36 PM 	Option: Buy to Open at Market 	AMD1922K42 	300 		$0.40 	$539.99 	$12,539.99 	$120,842.31
//11/19/2019 1:28 PM 	Stock: Sell at Market 	CRSP 	30 		$67.56 	$14.99 	$2,011.81 	$120,723.26
//11/19/2019 12:36 PM 	Stock: Buy at Market 	AMD 	500 		$40.67 	$14.99 	$20,347.74 	$120,568.80
//11/19/2019 12:35 PM 	Stock: Sell at Market 	LMT 	50 		$393.42 	$14.99 	$19,656.01 	$120,432.64
//11/14/2019 3:31 PM 	Short Stock: Short at Market 	INTC 	300 		$57.81 	$14.99 	$17,328.01 	$118,174.18
//11/14/2019 3:24 PM 	Option: Sell to Close at Market 	AMD1915K38.5 	100 		$0.44 	$189.99 	$4,210.01 	$118,326.72
//11/14/2019 3:10 PM 	Stock: Buy at Market 	AMD 	400 		$38.49 	$14.99 	$15,409.63 	$118,457.56
//11/12/2019 1:39 PM 	Option: Buy to Open at Market 	AMD1915K38.5 	100 		$0.13 	$189.99 	$1,489.99 	$115,600.78
//11/12/2019 1:27 PM 	Stock: Buy at Market 	INTC 	300 		$58.40 	$14.99 	$17,533.49 	$115,629.44
//11/12/2019 1:25 PM 	Stock: Sell at Market 	CRSP 	50 		$54.48 	$14.99 	$2,709.01 	$115,644.43
//11/11/2019 1:49 PM 	Stock: Buy at Market 	AMZN 	5 		$1,770.94 	$14.99 	$8,869.69 	$114,570.31
//11/11/2019 1:46 PM 	Stock: Buy at Market 	AJRD 	100 		$45.04 	$14.99 	$4,518.49 	$114,560.79
//11/11/2019 1:12 PM 	Stock: Sell at Market 	LMT 	50 		$382.13 	$14.99 	$19,091.51 	$114,548.59
//11/5/2019 10:28 AM 	Stock: Sell at Market 	QQQX 	250 		$23.56 	$14.99 	$5,873.79 	$113,637.91
//11/4/2019 9:59 AM 	Short Stock: Short at Market Open 	QQQX 	250 		$23.60 	$14.99 	$5,885.01 	$114,839.65
//10/31/2019 2:33 PM 	Stock: Sell at Market 	AMD 	275 		$34.21 	$14.99 	$9,392.79 	$113,515.93
//10/29/2019 2:18 PM 	Short Stock: Short at Market 	QQQX 	250 		$23.15 	$14.99 	$5,772.51 	$112,694.40
//10/29/2019 2:17 PM 	Stock: Buy at Market 	LMT 	90 		$371.61 	$14.99 	$33,459.89 	$112,709.39
//10/29/2019 2:05 PM 	Short Stock: Short at Market 	INTC 	200 		$56.53 	$14.99 	$11,291.01 	$112,687.84
//10/29/2019 2:03 PM 	Stock: Sell at Market 	CRSP 	500 		$47.63 	$14.99 	$23,800.01 	$112,727.04
//10/29/2019 2:02 PM 	Stock: Sell at Market 	AMD 	1000 		$33.26 	$14.99 	$33,244.51 	$112,742.03
//10/21/2019 1:32 PM 	Stock: Sell at Market 	RTN 	25 		$202.59 	$14.99 	$5,049.76 	$106,260.27
//10/21/2019 1:30 PM 	Stock: Sell at Market 	AMD 	250 		$32.18 	$14.99 	$8,028.76 	$106,173.00
//10/4/2019 1:56 PM 	Stock: Buy at Market 	CRSP 	300 		$39.97 	$14.99 	$12,005.99 	$100,637.21
//9/24/2019 3:29 PM 	Cover Stock: Cover at Market 	F 	1000 		$9.09 	$14.99 	$9,104.99 	$104,231.15
//9/20/2019 9:59 AM 	Stock: Buy at Market Open 	NOC 	20 		$376.57 	$14.99 	$7,546.39 	$107,740.95
//9/20/2019 9:59 AM 	Stock: Buy at Market Open 	LMT 	50 		$394.49 	$14.99 	$19,739.49 	$107,742.94
//9/20/2019 9:59 AM 	Stock: Buy at Market Open 	RTN 	25 		$199.69 	$14.99 	$5,007.24 	$107,752.68
//9/20/2019 9:59 AM 	Stock: Buy at Market Open 	AJRD 	100 		$52.13 	$14.99 	$5,227.99 	$107,751.67
//9/17/2019 10:00 AM 	Stock: Sell at Market Open 	LMT 	40 		$392.47 	$14.99 	$15,683.81 	$109,050.41
//9/13/2019 3:42 PM 	Stock: Sell at Market 	AMZN 	5 		$1,836.45 	$14.99 	$9,167.26 	$107,411.42
//9/13/2019 3:42 PM 	Stock: Sell at Market 	SPY 	30 		$300.94 	$14.99 	$9,013.09 	$107,426.41
//9/13/2019 3:41 PM 	Stock: Sell at Market 	NVDA 	50 		$181.98 	$14.99 	$9,084.01 	$107,441.40
//9/11/2019 11:16 AM 	Stock: Sell at Market 	CELG 	25 		$98.44 	$14.99 	$2,446.01 	$106,625.41
//9/11/2019 11:15 AM 	Stock: Sell at Market 	BAC 	50 		$29.24 	$14.99 	$1,446.79 	$106,479.48
//9/11/2019 11:14 AM 	Stock: Sell at Market 	TSLA 	100 		$242.04 	$14.99 	$24,188.51 	$106,494.47
//9/10/2019 10:01 AM 	Short Stock: Short at Market Open 	F 	1000 		$9.08 	$14.99 	$9,065.01 	$103,701.28
//8/28/2019 3:56 PM 	Stock: Sell at Market 	CELG 	75 		$97.23 	$14.99 	$7,277.26 	$101,678.49
//8/27/2019 3:23 PM 	Stock: Buy at Market 	CRSP 	150 		$44.84 	$14.99 	$6,740.99 	$99,912.81
//8/26/2019 10:02 AM 	Stock: Buy at Market Open 	AMZN 	10 		$1,766.91 	$14.99 	$17,684.09 	$100,336.37
//8/23/2019 3:32 PM 	Cover Stock: Cover at Market 	TSLA 	140 		$213.61 	$14.99 	$29,920.39 	$99,491.24
//8/19/2019 11:35 AM 	Stock: Buy at Market 	NVDA 	50 		$168.93 	$14.99 	$8,461.49 	$104,501.83
//8/14/2019 10:38 AM 	Stock: Buy at Market 	AMD 	250 		$30.50 	$14.99 	$7,639.97 	$100,980.52
//8/6/2019 10:04 AM 	Stock: Buy at Market Open 	AMD 	125 		$28.86 	$14.99 	$3,622.49 	$98,336.38
//8/1/2019 3:19 PM 	Stock: Buy at Market 	CRSP 	150 		$52.41 	$14.99 	$7,876.49 	$102,259.67
//8/1/2019 3:16 PM 	Stock: Buy at Market 	LMT 	50 		$363.48 	$14.99 	$18,188.99 	$102,274.66
//8/1/2019 3:14 PM 	Stock: Buy at Market 	SPY 	30 		$295.34 	$14.99 	$8,875.19 	$102,282.73
//8/1/2019 3:10 PM 	Cover Stock: Cover at Market 	NVDA 	60 		$164.22 	$14.99 	$9,868.19 	$102,334.86
//8/1/2019 3:05 PM 	Stock: Buy at Market 	AMD 	500 		$29.24 	$14.99 	$14,634.99 	$101,976.00
//7/31/2019 3:09 PM 	Stock: Buy at Limit 	AMD 	500 	$30.65 	$30.63 	$24.99 	$15,339.99 	$102,525.53
//7/25/2019 12:12 PM 	Short Stock: Short at Market 	NVDA 	40 		$174.33 	$14.99 	$6,958.01 	$103,911.69
//7/25/2019 12:10 PM 	Short Stock: Short at Market 	TSLA 	100 		$227.35 	$14.99 	$22,720.01 	$103,942.52
//7/25/2019 12:02 PM 	Stock: Sell at Market 	BAC 	100 		$30.48 	$14.99 	$3,032.51 	$103,963.77
//7/25/2019 11:34 AM 	Stock: Sell at Market 	NVDA 	60 		$174.93 	$14.99 	$10,480.81 	$104,269.78
//7/25/2019 10:00 AM 	Stock: Buy at Market Open 	TSLA 	100 		$233.50 	$14.99 	$23,364.99 	$104,610.75
//7/16/2019 10:00 AM 	Stock: Sell at Market Open 	AMD 	250 		$34.30 	$14.99 	$8,560.01 	$105,042.39
//7/15/2019 10:04 AM 	Stock: Sell at Market Open 	GD 	20 		$185.84 	$14.99 	$3,701.81 	$104,678.28
//7/15/2019 10:04 AM 	Stock: Sell at Market Open 	BAC 	170 		$29.50 	$14.99 	$5,000.01 	$104,643.97
//7/15/2019 10:01 AM 	Stock: Sell at Market Open 	AMD 	500 		$33.34 	$14.99 	$16,655.01 	$104,698.96
//7/15/2019 10:01 AM 	Stock: Sell at Market Open 	MU 	50 		$44.69 	$14.99 	$2,219.51 	$104,708.45
//7/15/2019 10:01 AM 	Stock: Sell at Market Open 	FB 	20 		$204.25 	$14.99 	$4,070.01 	$104,701.84
//7/3/2019 10:15 AM 	Stock: Sell at Limit 	TSLA 	20 	$237.20 	$237.26 	$24.99 	$4,720.21 	$101,430.17
//6/24/2019 11:00 AM 	Stock: Buy at Limit 	CELG 	100 	$94.10 	$94.08 	$24.99 	$9,432.99 	$98,836.52
//6/21/2019 2:34 PM 	Stock: Sell at Market 	INCY 	50 		$87.93 	$14.99 	$4,381.26 	$98,928.29
//6/21/2019 9:57 AM 	Stock: Sell at Market Open 	LMT 	50 		$362.23 	$14.99 	$18,096.51 	$100,096.99
//6/20/2019 9:57 AM 	Stock: Buy at Market Open 	ALGN 	20 		$302.03 	$14.99 	$6,055.59 	$101,420.77
//6/19/2019 9:57 AM 	Stock: Buy at Market Open 	AMD 	500 		$30.67 	$14.99 	$15,347.49 	$99,926.20
//6/19/2019 9:57 AM 	Stock: Sell at Market Open 	CRSP 	50 		$47.98 	$14.99 	$2,384.01 	$99,936.69
//6/17/2019 3:40 PM 	Stock: Sell at Market 	CRSP 	50 		$46.70 	$14.99 	$2,320.01 	$98,238.52
//6/14/2019 9:58 AM 	Stock: Buy at Market Open 	NVDA 	50 		$144.51 	$14.99 	$7,240.49 	$98,471.62
//6/14/2019 9:58 AM 	Stock: Buy at Market Open 	VMW 	30 		$174.00 	$14.99 	$5,234.99 	$98,505.96
//6/12/2019 3:40 PM 	Short Stock: Short at Market 	NVDA 	20 		$145.88 	$14.99 	$2,902.61 	$98,935.35
//6/12/2019 3:31 PM 	Stock: Buy at Market 	LMT 	50 		$344.89 	$14.99 	$17,259.49 	$98,921.28
//6/12/2019 3:27 PM 	Stock: Sell at Market 	CELG 	10 		$96.49 	$14.99 	$949.86 	$98,867.98
//6/12/2019 9:57 AM 	Stock: Sell at Market Open 	CRSP 	100 		$43.64 	$14.99 	$4,349.01 	$98,863.15
//6/11/2019 11:33 AM 	Stock: Buy at Limit 	CRSP 	200 	$42.89 	$42.46 	$24.99 	$8,515.99 	$99,178.34
//6/10/2019 3:55 PM 	Stock: Sell at Market 	PYPL 	100 		$115.93 	$14.99 	$11,577.51 	$99,675.85
//6/10/2019 3:54 PM 	Stock: Buy at Limit 	AMD 	500 	$33.55 	$33.41 	$24.99 	$16,727.49 	$99,700.84
//6/7/2019 11:36 AM 	Stock: Sell at Limit 	AMD 	500 	$32.10 	$32.16 	$24.99 	$16,055.06 	$99,089.43
//6/7/2019 9:58 AM 	Stock: Sell at Limit 	LMT 	10 	$352.60 	$353.43 	$24.99 	$3,509.26 	$98,807.70
//6/5/2019 10:06 AM 	Cover Stock: Cover at Limit 	NVDA 	20 	$143.00 	$141.61 	$24.99 	$2,857.19 	$97,257.18
//6/5/2019 10:06 AM 	Stock: Sell at Limit 	LMT 	10 	$349.34 	$349.85 	$24.99 	$3,473.53 	$97,282.17
//6/5/2019 9:58 AM 	Stock: Buy at Limit 	BAC 	300 	$27.93 	$27.51 	$24.99 	$8,277.99 	$97,502.77
//6/5/2019 9:58 AM 	Stock: Sell at Limit 	AMD 	500 	$29.55 	$29.77 	$24.99 	$14,860.01 	$97,527.76
//6/4/2019 9:58 AM 	Short Stock: Short at Limit 	TSLA 	40 	$178.95 	$181.99 	$24.99 	$7,254.61 	$94,930.40
//6/4/2019 9:58 AM 	Short Stock: Short at Limit 	INTC 	100 	$43.45 	$44.00 	$24.99 	$4,375.01 	$94,955.39
//5/31/2019 2:24 PM 	Stock: Buy at Limit 	AMD 	1000 	$27.76 	$27.76 	$24.99 	$27,784.89 	$95,961.33
//5/28/2019 3:58 PM 	Stock: Sell at Market 	AMD 	1000 		$29.06 	$14.99 	$29,045.01 	$97,112.87
//5/22/2019 10:01 AM 	Short Stock: Short at Market Open 	NVDA 	20 		$153.50 	$14.99 	$3,055.01 	$96,338.56
//5/10/2019 10:27 AM 	Stock: Buy at Limit 	VMW 	10 	$197.70 	$197.60 	$24.99 	$2,000.99 	$97,277.63
//5/7/2019 9:58 AM 	Stock: Buy at Limit 	BAC 	20 	$30.50 	$30.10 	$24.99 	$626.99 	$98,715.89
//5/7/2019 9:58 AM 	Stock: Buy at Limit 	PYPL 	100 	$110.86 	$109.75 	$24.99 	$10,999.99 	$98,740.88
//5/7/2019 9:58 AM 	Stock: Buy at Limit 	INCY 	50 	$85.10 	$83.96 	$24.99 	$4,222.99 	$98,765.87
//5/7/2019 9:58 AM 	Stock: Buy at Limit 	GD 	20 	$175.40 	$173.24 	$24.99 	$3,489.79 	$98,790.86
//5/7/2019 9:58 AM 	Stock: Buy at Limit 	LMT 	20 	$335.80 	$332.67 	$24.99 	$6,678.43 	$98,815.85
//5/3/2019 3:50 PM 	Cover Stock: Cover at Market 	OXY 	10 		$58.03 	$14.99 	$595.29 	$100,152.28
//5/3/2019 10:04 AM 	Stock: Buy at Market Open 	AMD 	750 		$28.30 	$14.99 	$21,239.99 	$99,861.06
//5/1/2019 9:58 AM 	Short Stock: Short at Limit 	OXY 	10 	$55.50 	$58.95 	$24.99 	$564.51 	$100,095.16
//4/29/2019 3:14 PM 	Stock: Buy at Market 	GDP 	150 		$13.87 	$14.99 	$2,095.49 	$99,966.97
//4/29/2019 3:10 PM 	Stock: Sell at Market 	CELG 	40 		$94.78 	$14.99 	$3,776.21 	$99,978.27
//4/29/2019 10:13 AM 	Stock: Buy at Market Open 	AMZN 	5 		$1,949.00 	$14.99 	$9,759.99 	$99,937.26
//4/29/2019 10:13 AM 	Stock: Buy at Market Open 	TSLA 	20 		$235.86 	$14.99 	$4,732.19 	$99,889.45
//4/26/2019 10:02 AM 	Stock: Buy at Market Open 	MU 	50 		$41.49 	$14.99 	$2,089.49 	$99,662.22
//4/26/2019 10:02 AM 	Stock: Buy at Market Open 	FB 	20 		$192.50 	$14.99 	$3,864.99 	$99,719.61
//4/26/2019 10:01 AM 	Stock: Buy at Market Open 	CELG 	50 		$94.20 	$14.99 	$4,724.99 	$99,746.10
//4/26/2019 10:01 AM 	Stock: Buy at Market Open 	OXY 	50 		$61.60 	$14.99 	$3,094.99 	$99,777.09
//4/26/2019 10:01 AM 	Stock: Buy at Market Open 	NVDA 	10 		$180.71 	$14.99 	$1,822.09 	$99,827.30
//4/26/2019 10:01 AM 	Stock: Buy at Market Open 	AMD 	250 		$27.66 	$14.99 	$6,929.99 	$99,970.16
//4/26/2019 10:01 AM 	Stock: Buy at Market Open 	QQQX 	250 		$23.22 	$14.99 	$5,819.99 	$100,000.00
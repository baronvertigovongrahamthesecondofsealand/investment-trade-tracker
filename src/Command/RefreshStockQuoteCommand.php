<?php

namespace App\Command;

use AlphaVantage\Client as AlphaVantageClient;
use AlphaVantage\Options;
use App\Entity\Stock;
use App\Services\TradeManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RefreshStockQuoteCommand extends Command
{
    protected static $defaultName = 'app:stocks:refresh';

    /** @var EntityManagerInterface */
    protected $em;

    /** @var TradeManager */
    protected $tradeManager;

    /** @var AlphaVantageClient */
    protected $avClient;

    public function __construct(EntityManagerInterface $entityManager, TradeManager $tradeManager) {
        $this->em = $entityManager;
        $this->tradeManager = $tradeManager;
        $options = new Options();
        $options->setApiKey($_ENV['ALPHAVANTAGE_KEY']);
        $this->avClient = new AlphaVantageClient($options);

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Refreshes the oldest stock quote')
//            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
//            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

//        for ($i = 0; $i < 5; $i++) {
            $lastRefreshedStock = $this->em->getRepository('App:Stock')->findOneBy([
                'stockType' => 'Stock'
            ], ['refreshedAt' => 'ASC']);

            $io->writeln('refreshing '.$lastRefreshedStock->getSymbol().'...');

            $quote = [];

            try {
                $quote = $this->avClient->timeSeries()->daily($lastRefreshedStock->getSymbol());
            } catch (\Exception $e) {
                $io->error($e->getMessage());
            }

            $lastRefreshed = new \DateTime($quote['Meta Data']['3. Last Refreshed']);
            $lastPrice = $quote['Time Series (Daily)'][$lastRefreshed->format('Y-m-d')];

            $lastRefreshedStock->setPrice($lastPrice['4. close']);
            $lastRefreshedStock->setRefreshedAt(new \DateTime());

            $this->em->persist($lastRefreshedStock);
            $this->em->flush();
//        }

//        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return 0;
    }
}

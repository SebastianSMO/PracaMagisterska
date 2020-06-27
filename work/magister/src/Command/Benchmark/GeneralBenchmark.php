<?php


namespace App\Command\Benchmark;


use App\Benchmark\BenchmarkExecutor;
use App\Connection\Postgres3NF;
use App\Connection\PostgresNon3NF;
use Doctrine\DBAL\Logging\DebugStack;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class GeneralBenchmark extends Command
{
    protected static $defaultName = "benchmark";

    private $nf;

    private $non3NF;

    public function __construct(Postgres3NF $nf, PostgresNon3NF $non3NF, string $name = null)
    {
        parent::__construct($name);
        $this->nf = $nf;
        $this->non3NF = $non3NF;
    }

    public function configure()
    {
        parent::configure();
        $this->addArgument("benchmarkClass", InputArgument::REQUIRED, "Klasa dziedziczaca po AbstractBenchmark");
        $this->addArgument("benchmarkIterations", InputArgument::REQUIRED, "Liczba iteracji wybranego benchmarku");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set("memory_limit", "10G");
        $benchmarkClass = "\\App\\Benchmark\\" . $input->getArgument("benchmarkClass");
        $benchmarkIterations = $input->getArgument("benchmarkIterations");

        $stopWatch = new Stopwatch();
        $stopWatch->start("command-start");

        $debugStack3nf = new DebugStack();
        $this->nf->getConnection()->getConfiguration()->setSQLLogger($debugStack3nf);
        $debugStackNon3nf = new DebugStack();
        $this->non3NF->getConnection()->getConfiguration()->setSQLLogger($debugStackNon3nf);

        $benchmarkExecutor = new BenchmarkExecutor(
            (int) $benchmarkIterations, new $benchmarkClass($this->nf, $this->non3NF)
        );
        $benchmarkExecutor->setOutputInterface($output);
        $benchmarkExecutor->runBenchmarks();

        $output->writeln("\nZapytania zostaly wykonane.");
        $output->writeln("Czasy (w milisekundach):\n");

        $output->writeln("iteracja\t\t3nf\t\tnon3nf");
        for ($i = 1; $i <= count($debugStackNon3nf->queries); $i++)
        {
            $output->writeln(
                ($i) . "\t\t" .
                (number_format($debugStack3nf->queries[$i]["executionMS"] * 1000, 2, ",", "")) . "\t\t" .
                (number_format($debugStackNon3nf->queries[$i]["executionMS"] * 1000, 2, ",", ""))
            );
        }
        $output->writeln("\nSrednia 3nf:\t" . (number_format(array_sum(array_column($debugStack3nf->queries, "executionMS")) / count($debugStack3nf->queries) * 1000, 2, ",", "")));
        $output->writeln("Srednia non3nf:\t" . (number_format(array_sum(array_column($debugStackNon3nf->queries, "executionMS")) / count($debugStackNon3nf->queries) * 1000, 2, ",", "")));

        $time = $stopWatch->stop("command-start");
        $output->writeln("\n" . $time);
    }

}

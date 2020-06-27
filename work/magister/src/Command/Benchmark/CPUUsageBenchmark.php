<?php


namespace App\Command\Benchmark;


use App\Benchmark\StarsBench;
use App\Connection\Postgres3NF;
use App\Connection\PostgresNon3NF;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CPUUsageBenchmark extends Command
{
    protected static $defaultName = "test-cpu-usage";

    private $nf;

    private $non3NF;

    const ITERATIONS = 50;

    public function __construct(Postgres3NF $nf, PostgresNon3NF $non3NF, string $name = null)
    {
        parent::__construct($name);
        $this->nf = $nf;
        $this->non3NF = $non3NF;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set("memory_limit", "10G");
        $starsBenchmark = new StarsBench($this->nf, $this->non3NF);

        $output->writeln("Rozpoczynam test 3nf. Aktualna data: " . date("Y-m-d H:i:s"));

        $progressBar = new ProgressBar($output, self::ITERATIONS);
        $progressBar->start();

        foreach (range(1, self::ITERATIONS) as $i)
        {
            $progressBar->advance();
            $starsBenchmark->getRows3NF();
            sleep(1);
        }

        $progressBar->finish();
        $output->writeln("\n\nCzekam 15 sekund\n");
        sleep(15);

        $output->writeln("Rozpoczynam test non3nf. Aktualna data: " . date("Y-m-d H:i:s"));

        $progressBar = new ProgressBar($output, self::ITERATIONS);
        $progressBar->start();

        foreach (range(1, self::ITERATIONS) as $i)
        {
            $progressBar->advance();
            $starsBenchmark->getRowsNon3NF();
            sleep(1);
        }

        $progressBar->finish();
        $output->writeln("\n\nKoniec testu" . date("Y-m-d H:i:s"));
    }

}

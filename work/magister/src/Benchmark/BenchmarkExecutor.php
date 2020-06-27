<?php


namespace App\Benchmark;


use Symfony\Component\Console\Output\OutputInterface;

class BenchmarkExecutor
{
    private AbstractBenchmark $benchmark;

    private int $iterations;

    /**
     * @var OutputInterface
     */
    private OutputInterface $output;

    public function __construct(int $iterations, AbstractBenchmark $benchmarks)
    {
        $this->benchmark = $benchmarks;
        $this->iterations = $iterations;
    }

    public function setOutputInterface(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function runBenchmarks()
    {
        $this->output->writeln("Rozpoczynam benchmark:");
        $this->output->writeln($this->iterations . "x " . $this->benchmark->getBenchmarkDescription() . "\n");

        foreach (range(1, $this->iterations) as $i)
        {
            $nfRows = $this->benchmark->getRows3NF();
            $non3nfRows = $this->benchmark->getRowsNon3NF();
            $areTheSame = $this->areTheSame($nfRows, $non3nfRows) ? "tak" : "nie";

            $this->output->writeln(
                "Wykonano zapytanie: " . $i . ", rekordow: " . (count($nfRows)) . " - " . (count($non3nfRows)) . ", integralnosc danych: " . $areTheSame
            );
            $this->benchmark->afterOneIteration();
        }
    }

    private function areTheSame(array $rows3nf, array $rowsNon3nf) : bool
    {
        $nf = json_encode($rows3nf);
        $non3nf = json_encode($rowsNon3nf);

        if (md5($nf) == md5($non3nf)) {
            return true;
        }

        return false;
    }

}
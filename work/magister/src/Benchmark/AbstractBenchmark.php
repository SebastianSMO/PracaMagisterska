<?php


namespace App\Benchmark;


use App\Connection\Postgres3NF;
use App\Connection\PostgresNon3NF;
use Doctrine\DBAL\Driver\Connection;

abstract class AbstractBenchmark
{
    protected Connection $nf;

    protected Connection $non3nf;

    public function __construct(Postgres3NF $nf, PostgresNon3NF $non3nf)
    {
        $this->nf = $nf->getConnection();
        $this->non3nf = $non3nf->getConnection();
    }

    abstract public function getRows3NF() : array;

    abstract public function getRowsNon3NF() : array;

    abstract public function getBenchmarkDescription() : string;

    public function afterOneIteration() : void
    {

    }

}

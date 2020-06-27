<?php

namespace App\Command;

use App\Connection\Postgres3NF;
use App\Connection\PostgresNon3NF;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DemoCommand extends Command
{
    protected static $defaultName = "demo";

    private Connection $nf;

    private Connection $non3nf;

    public function __construct(Postgres3NF $nf, PostgresNon3NF $non3nf, string $name = null)
    {
        parent::__construct($name);
        $this->nf = $nf->getConnection();
        $this->non3nf = $non3nf->getConnection();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Demo");
    }

}

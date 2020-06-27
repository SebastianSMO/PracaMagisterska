<?php


namespace App\Command\Non3NF;


use App\Connection\Postgres3NF;
use App\Connection\PostgresNon3NF;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class CreateDatabaseJOIN extends Command
{
    protected static $defaultName = "non3nf:create-database-use-join";

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
        $stopWatch = new Stopwatch();
        $stopWatch->start("command-start");
        $output->writeln("Tworze baze non-3NF (z wykorzystaniem JOIN i GROUP BY)");

        $output->writeln("Usuwam stara tabele non3nf");
        $this->nf->executeQuery("
            DROP TABLE IF EXISTS non3nf;
        ");

        $output->writeln("Tworze tabele non3nf");
        $this->nf->executeQuery("
            CREATE TABLE non3nf (
                product_id BIGINT PRIMARY KEY,
                product_name VARCHAR(255),
                product_tax DECIMAL(10,2),
                product_gross_price DECIMAL(10,2),
                product_created TIMESTAMP WITH TIME ZONE,
                brand_id BIGINT,
                brand_name VARCHAR(255),
                category_id BIGINT,
                category_name VARCHAR(255),
                fhead_ids BIGINT[],
                fvalue_ids BIGINT[],
                comment_count BIGINT,
                stars_count BIGINT,
                stars_avg_mark DECIMAL(10,2),
                sold_count BIGINT
            );
        ");

        $output->writeln("Uzupelniam tabele non3nf na podstawie danych w bazie 3NF");
        $this->nf->executeQuery("
            INSERT INTO non3nf
            SELECT
                p.product_id AS \"product_id\",
                p.product_name AS \"product_name\",
                p.product_tax AS \"product_tax\",
                MIN(sp.product_gross_price) AS \"product_gross_price\",
                MIN(p.product_created) AS \"product_created\",
                MIN(b.brand_id) AS \"brand_id\",
                MIN(b.brand_name) AS \"brand_name\",
                MIN(c.category_id) AS \"category_id\",
                MIN(c.category_name) AS \"category_name\",
                ARRAY_AGG(DISTINCT fh.fhead_id) AS fhead_ids,
                ARRAY_AGG(DISTINCT fv.fvalue_id) AS fvalue_ids,
                COUNT(DISTINCT cm.comment_id) AS comment_count,
                COUNT(DISTINCT p_stars.ps_id) AS stars_count,
                AVG(p_stars.stars)::DECIMAL(10, 2) AS stars_avg_mark,
                (SELECT SUM(quantity) FROM product_sold WHERE product_sold.product_id = p.product_id) AS sold_count
            FROM product p
            INNER JOIN brand b ON b.brand_id = p.brand_id
            INNER JOIN category c ON c.category_id = p.category_id
            LEFT JOIN product_fvalue pv ON pv.product_id = p.product_id
            LEFT JOIN fvalue fv ON pv.fvalue_id = fv.fvalue_id
            LEFT JOIN fhead fh ON fh.fhead_id = fv.fhead_id
            LEFT JOIN comment cm ON cm.product_id = p.product_id
            LEFT JOIN supplier_product sp ON sp.product_id = p.product_id
            LEFT JOIN supplier s ON s.supplier_id = sp.supplier_id
            LEFT JOIN product_sold p_sold ON p.product_id = p_sold.product_id
            LEFT JOIN product_stars p_stars ON p.product_id = p_stars.product_id
            GROUP BY p.product_id
            ;
        ");

        $output->writeln("Tabela non3nf zostala uzupelniona");
        $output->writeln("Tworze indeksy");

        $output->writeln("\t-> non3nf.fvalue_ids (GIN)");
        $this->nf->executeQuery("
            CREATE INDEX non3nf_fvalue_ids ON non3nf USING GIN(fvalue_ids);
        ");

        $output->writeln("\t-> non3nf.category_id");
        $this->nf->executeQuery("
            CREATE INDEX non3nf_category_id ON non3nf(category_id);
        ");

        $output->writeln("\t-> non3nf.brand_id");
        $this->nf->executeQuery("
            CREATE INDEX non3nf_brand_id ON non3nf(brand_id);
        ");

        $output->writeln("\t-> non3nf.price");
        $this->nf->executeQuery("
            CREATE INDEX non3nf_price ON non3nf(product_gross_price);
        ");

        $output->writeln("Utworzono tabele non3nf (zdenormalizowana)");
        $time = $stopWatch->stop("command-start");
        $output->writeln($time);
    }

}

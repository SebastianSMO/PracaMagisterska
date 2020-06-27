<?php


namespace App\Command\Non3NF;

use App\Connection\Postgres3NF;
use App\Connection\PostgresNon3NF;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class CreateDatabase extends Command
{
    protected static $defaultName = "non3nf:create-database";

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
        $output->writeln("Tworze baze non-3NF (z wykorzystaniem podzapytan)");

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
                (SELECT MIN(product_gross_price) FROM supplier_product WHERE supplier_product.product_id = p.product_id)::DECIMAL(10,2) AS \"product_gross_price\",
                p.product_created AS \"product_created\",
                b.brand_id AS \"brand_id\",
                b.brand_name AS \"brand_name\",
                c.category_id AS \"category_id\",
                c.category_name AS \"category_name\",
                (
                    SELECT ARRAY_AGG(fhead.fhead_id) FROM fhead
                    INNER JOIN fvalue ON fvalue.fhead_id = fhead.fhead_id
                    INNER JOIN product_fvalue ON product_fvalue.fvalue_id = fvalue.fvalue_id
                    WHERE product_fvalue.product_id = p.product_id
                ) AS \"fhead_ids\",
                (SELECT ARRAY_AGG(fvalue_id) FROM product_fvalue WHERE product_fvalue.product_id = p.product_id) AS \"fvalue_ids\",
                (SELECT COUNT(DISTINCT comment_id) FROM comment WHERE comment.product_id = p.product_id) AS comment_count,
                (SELECT COUNT(DISTINCT ps_id) FROM product_stars WHERE product_stars.product_id = p.product_id) AS stars_count,
                (SELECT AVG(stars) FROM product_stars WHERE product_stars.product_id = p.product_id)::DECIMAL(10,2) AS stars_avg_mark,
                (SELECT SUM(quantity) FROM product_sold WHERE product_sold.product_id = p.product_id) AS sold_count
            FROM product p
            LEFT JOIN brand b ON b.brand_id = p.brand_id
            LEFT JOIN \"category\" c ON c.category_id = p.category_id
            ORDER BY p.product_id
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

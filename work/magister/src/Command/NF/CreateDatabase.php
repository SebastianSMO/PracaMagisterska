<?php

namespace App\Command\NF;

use App\Connection\Postgres3NF;
use App\Connection\PostgresNon3NF;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class CreateDatabase extends Command
{
    protected static $defaultName = "3nf:create-database";

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
        $output->writeln("Tworze tabele w bazie 3NF");

        $this->dropTables($output);
        $this->createTables($output);
        $this->createIndexes($output);

        $output->writeln("Utworzono tabele w bazie 3NF");
        $time = $stopWatch->stop("command-start");
        $output->writeln($time);
    }

    private function createTables(OutputInterface $output)
    {
        $output->writeln("\tTworze tabele category");
        $this->nf->executeQuery("
            CREATE TABLE category (
                category_id BIGSERIAL PRIMARY KEY,
                category_name VARCHAR(255) DEFAULT NULL
            );
        ");

        $output->writeln("\tTworze tabele brand");
        $this->nf->executeQuery("
            CREATE TABLE brand (
                brand_id BIGSERIAL PRIMARY KEY,
                brand_name VARCHAR(255) DEFAULT NULL
            );
        ");

        $output->writeln("\tTworze tabele product");
        $this->nf->executeQuery("
            CREATE TABLE product (
                product_id BIGSERIAL PRIMARY KEY,
                product_name VARCHAR(255) DEFAULT NULL,
                category_id BIGINT DEFAULT NULL,
                brand_id BIGINT DEFAULT NULL,
                -- product_gross_price DECIMAL(10,2) DEFAULT 0, -- cena jest wyliczana na podstawie najtanszego dostawcy
                product_tax DECIMAL(10,2) DEFAULT 0,
                product_created TIMESTAMP WITH TIME ZONE,
            
                FOREIGN KEY (category_id) REFERENCES category(category_id),
                FOREIGN KEY (brand_id) REFERENCES brand(brand_id)
            );
        ");

        $output->writeln("\tTworze tabele fhead");
        $this->nf->executeQuery("
            CREATE TABLE fhead (
                fhead_id BIGSERIAL PRIMARY KEY,
                fhead_name VARCHAR(255)
            );
        ");

        $output->writeln("\tTworze tabele fvalue");
        $this->nf->executeQuery("
            CREATE TABLE fvalue (
                fvalue_id BIGSERIAL PRIMARY KEY,
                fhead_id BIGINT DEFAULT NULL,
                fvalue_name VARCHAR(255),
            
                FOREIGN KEY (fhead_id) REFERENCES fhead(fhead_id)
            );
        ");

        $output->writeln("\tTworze tabele product_fvalue");
        $this->nf->executeQuery("
            CREATE TABLE product_fvalue (
                product_id BIGINT,
                fvalue_id BIGINT,
            
                PRIMARY KEY (product_id, fvalue_id),
                FOREIGN KEY (fvalue_id) REFERENCES fvalue(fvalue_id),
                FOREIGN KEY (product_id) REFERENCES product(product_id)
            );
        ");

        $output->writeln("\tTworze tabele supplier");
        $this->nf->executeQuery("
            CREATE TABLE supplier (
                supplier_id BIGSERIAL PRIMARY KEY,
                supplier_name VARCHAR(255)
            );
        ");

        $output->writeln("\tTworze tabele supplier_product");
        $this->nf->executeQuery("
            CREATE TABLE supplier_product (
                product_id BIGINT DEFAULT NULL,
                supplier_id BIGINT DEFAULT NULL,
                product_gross_price DECIMAL(10,2),
                product_quantity INT,
            
                PRIMARY KEY (product_id, supplier_id),
                FOREIGN KEY (product_id) REFERENCES product(product_id),
                FOREIGN KEY (supplier_id) REFERENCES supplier(supplier_id)
            );
        ");

        $output->writeln("\tTworze tabele user");
        $this->nf->executeQuery("
            CREATE TABLE \"user\" (
                user_id BIGSERIAL PRIMARY KEY,
                user_email VARCHAR(255)
            );
        ");

        $output->writeln("\tTworze tabele comment");
        $this->nf->executeQuery("
            CREATE TABLE comment (
                comment_id BIGSERIAL PRIMARY KEY,
                user_id BIGINT DEFAULT NULL,
                product_id BIGINT DEFAULT NULL,
                comment_text TEXT,
            
                FOREIGN KEY (user_id) REFERENCES \"user\"(user_id),
                FOREIGN KEY (product_id) REFERENCES product(product_id)
            );
        ");

        $output->writeln("\tTworze tabele product_stars");
        $this->nf->executeQuery("
            CREATE TABLE product_stars (
                ps_id BIGSERIAL PRIMARY KEY,
                product_id BIGINT,
                user_id BIGINT,
                stars INTEGER,
            
                FOREIGN KEY (user_id) REFERENCES \"user\"(user_id),
                FOREIGN KEY (product_id) REFERENCES product(product_id)
            );
        ");

        $output->writeln("\tTworze tabele product_sold");
        $this->nf->executeQuery("
            CREATE TABLE product_sold (
                ps_id BIGSERIAL PRIMARY KEY,
                product_id BIGINT,
                user_id BIGINT,
                quantity INTEGER,
                sold_date TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                
                FOREIGN KEY (product_id) REFERENCES product(product_id)
            );
        ");
    }

    private function dropTables(OutputInterface $output)
    {
        $output->writeln("\tUsuwam wszystkie tabele w bazie");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS product_sold;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS product_stars;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS comment;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS review;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS supplier_product;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS supplier;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS product_fvalue;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS fvalue;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS fhead;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS product;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS category;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS brand;
        ");

        $this->nf->executeQuery("
            DROP TABLE IF EXISTS \"user\";
        ");
    }

    private function createIndexes(OutputInterface $output)
    {
        $output->writeln("Tworze indeksy");

        $output->writeln("\t-> product.brand_id");
        $this->nf->executeQuery("
            CREATE INDEX product_brand_id ON product(brand_id);
        ");

        $output->writeln("\t-> product.category_id");
        $this->nf->executeQuery("
            CREATE INDEX product_category_id ON product(category_id);
        ");

        $output->writeln("\t-> product.product_created");
        $this->nf->executeQuery("
            CREATE INDEX product_product_created ON product(product_created);
        ");

        $output->writeln("\t-> fvalue.fhead_id");
        $this->nf->executeQuery("
            CREATE INDEX fvalue_fhead_id ON fvalue(fhead_id);
        ");

        $output->writeln("\t-> product_fvalue.fhead_id");
        $this->nf->executeQuery("
            CREATE INDEX product_fvalue_fvalue_id ON product_fvalue(fvalue_id);
        ");

        $output->writeln("\t-> supplier_product.supplier_id");
        $this->nf->executeQuery("
            CREATE INDEX supplier_product_supplier_id ON supplier_product(supplier_id);
        ");

        $output->writeln("\t-> comment.user_id");
        $this->nf->executeQuery("
            CREATE INDEX comment_user_id ON comment(user_id);
        ");

        $output->writeln("\t-> comment.product_id");
        $this->nf->executeQuery("
            CREATE INDEX comment_product_id ON comment(product_id);
        ");

        $output->writeln("\t-> product_stars.user_id");
        $this->nf->executeQuery("
            CREATE INDEX product_stars_user_id ON product_stars(user_id);
        ");

        $output->writeln("\t-> product_stars.product_id");
        $this->nf->executeQuery("
            CREATE INDEX product_stars_product_id ON product_stars(product_id);
        ");

        $output->writeln("\t-> product_sold.user_id");
        $this->nf->executeQuery("
            CREATE INDEX product_sold_user_id ON product_sold(user_id);
        ");

        $output->writeln("\t-> product_sold.product_id");
        $this->nf->executeQuery("
            CREATE INDEX product_sold_product_id ON product_sold(product_id);
        ");
    }

}

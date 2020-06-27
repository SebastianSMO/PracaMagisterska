<?php

namespace App\Command\NF;

use App\Connection\Postgres3NF;
use App\Connection\PostgresNon3NF;
use App\Kernel;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class GenerateData extends Command
{
    protected static $defaultName = "3nf:generate-data";

    private Connection $nf;

    private Connection $non3nf;

    private string $projectRoot;

    private const PACKAGE_SIZE = 10000;

    private const FHEAD_NUMBER = 10000;

    private const FVALUE_NUMBER = 50000;

    private const BRAND_NUMBER = 100;

    private const PRODUCT_NUMBER = 50000;

    private const PRODUCT_FVALUE_NUMBER = 450000;

    private const SUPPLIER_NUMBER = 100;

    private const SUPPLIER_PRODUCT_NUMBER = 250000;

    private const USER_NUMBER = 35000;

    private const COMMENT_NUMBER = 200000;

    private const STARS_NUMBER = 500000;

    private const SOLD_NUMBER = 500000;

    public function __construct(Postgres3NF $nf, PostgresNon3NF $non3nf, KernelInterface $kernel, string $name = null)
    {
        parent::__construct($name);
        $this->nf = $nf->getConnection();
        $this->non3nf = $non3nf->getConnection();
        $this->projectRoot = $kernel->getProjectDir();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set("memory_limit", "10G");
        $stopWatch = new Stopwatch();
        $stopWatch->start("command-start");
        $output->writeln("Generuje losowe dane dla bazy 3NF");

        $output->writeln("\t-> category");
        $this->insertCategories($output);

        $output->writeln("\t-> fhead");
        $this->insertFheads($output);

        $output->writeln("\t-> fvalue");
        $this->insertFvalues($output);

        $output->writeln("\t-> brand");
        $this->insertBrands($output);

        $output->writeln("\t-> product");
        $this->insertProducts($output);

        $output->writeln("\t-> product_fvalue");
        $this->insertProductFvalue($output);

        $output->writeln("\t-> supplier");
        $this->insertSuppliers($output);

        $output->writeln("\t-> supplier_product");
        $this->insertSupplierProduct($output);

        $output->writeln("\t-> user");
        $this->insertUsers($output);

        $output->writeln("\t-> comment");
        $this->insertComments($output);

        $output->writeln("\t-> product_stars");
        $this->insertProductStars($output);

        $output->writeln("\t-> product_sold");
        $this->insertProductSold($output);

        $output->writeln("Generowanie danych zostalo zakonczone!");
        $time = $stopWatch->stop("command-start");
        $output->writeln($time);
    }

    private function insertCategories(OutputInterface $output)
    {
        $categoriesFile = fopen($this->projectRoot . "/src/Data/Categories.txt", "r");
        $values = "";
        $i = 0;
        while ($line = fgets($categoriesFile))
        {
            $line = $this->nf->quote(trim($line));
            $values .= "($line),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO category (category_name) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO category (category_name) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
        fclose($categoriesFile);
    }

    private function insertFheads(OutputInterface $output)
    {
        $values = "";
        $i = 0;
        while ($i < self::FHEAD_NUMBER)
        {
            $fheadName = $this->nf->quote($this->generateRandomString(50));
            $values .= "($fheadName),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO fhead (fhead_name) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO fhead (fhead_name) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
    }

    private function insertFvalues(OutputInterface $output)
    {
        $countFheads = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM fhead")->fetch()["count_rows"];

        $values = "";
        $i = 0;
        while ($i < self::FVALUE_NUMBER)
        {
            $fvalue = $this->nf->quote($this->generateRandomString(50));
            $fheadId = mt_rand(1, $countFheads - 1);
            $values .= "($fvalue, $fheadId),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO fvalue (fvalue_name, fhead_id) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO fvalue (fvalue_name, fhead_id) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
    }

    private function insertBrands(OutputInterface $output)
    {
        $values = "";
        $i = 0;
        while ($i < self::BRAND_NUMBER)
        {
            $fheadName = $this->nf->quote($this->generateRandomString(15));
            $values .= "($fheadName),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO brand (brand_name) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO brand (brand_name) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
    }

    private function insertProducts(OutputInterface $output)
    {
        $countCategories = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM category")->fetch()["count_rows"];
        $countBrands= $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM brand")->fetch()["count_rows"];

        $values = "";
        $i = 0;
        while ($i < self::PRODUCT_NUMBER)
        {
            $productName = $this->nf->quote($this->generateRandomString(100));
            $productTax = $this->nf->quote(1.23);
            $categoryId = mt_rand(1, $countCategories);
            $brandId = mt_rand(1, $countBrands);
            $created = $this->nf->quote($this->generateRandomDate("2000-01-01 00:00:00", date("Y-m-d H:i:s")));
            $values .= "($productName, $productTax, $categoryId, $brandId, $created),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO product (product_name, product_tax, category_id, brand_id, product_created) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO product (product_name, product_tax, category_id, brand_id, product_created) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
    }

    private function insertProductFvalue(OutputInterface $output)
    {
        $countFvalue = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM fvalue")->fetch()["count_rows"];
        $countProducts = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM product")->fetch()["count_rows"];

        $values = "";
        $i = 0;
        while ($i < self::PRODUCT_FVALUE_NUMBER)
        {
            $fvalueId = mt_rand(1, $countFvalue);
            $productId = mt_rand(1, $countProducts);
            $values .= "($productId, $fvalueId),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO product_fvalue (product_id, fvalue_id)
                    VALUES " . substr($values, 0, -2) . "
                    ON CONFLICT DO NOTHING
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO product_fvalue (product_id, fvalue_id)
                    VALUES " . substr($values, 0, -2) . "
                    ON CONFLICT DO NOTHING
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
    }

    private function insertSuppliers(OutputInterface $output)
    {
        $values = "";
        $i = 0;
        while ($i < self::SUPPLIER_NUMBER)
        {
            $supplierName = $this->nf->quote($this->generateRandomString(20));
            $values .= "($supplierName),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO supplier (supplier_name)
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO supplier (supplier_name)
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
    }

    private function insertSupplierProduct(OutputInterface $output)
    {
        $countProducts = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM product")->fetch()["count_rows"];
        $countSuppliers = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM supplier")->fetch()["count_rows"];

        $values = "";
        $i = 0;
        while ($i < self::SUPPLIER_PRODUCT_NUMBER)
        {
            $productId = mt_rand(1, $countProducts);
            $supplierId = mt_rand(1, $countSuppliers);
            $grossPrice = mt_rand(1, 9999);
            $productQuantity = mt_rand(0, 999);
            $values .= "($productId, $supplierId, $grossPrice, $productQuantity),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO supplier_product (product_id, supplier_id, product_gross_price, product_quantity)
                    VALUES " . substr($values, 0, -2) . "
                    ON CONFLICT DO NOTHING
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO supplier_product (product_id, supplier_id, product_gross_price, product_quantity)
                    VALUES " . substr($values, 0, -2) . "
                    ON CONFLICT DO NOTHING
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
    }

    private function insertUsers(OutputInterface $output)
    {
        $values = "";
        $i = 0;
        while ($i < self::USER_NUMBER)
        {
            $userName = $this->nf->quote($this->generateRandomString(25));
            $values .= "($userName),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO \"user\" (user_email) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO \"user\" (user_email) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
    }

    private function insertComments(OutputInterface $output)
    {
        $countUsers = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM \"user\"")->fetch()["count_rows"];
        $countProducts = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM product")->fetch()["count_rows"];

        $values = "";
        $i = 0;
        while ($i < self::COMMENT_NUMBER)
        {
            $userId = mt_rand(1, $countUsers);
            $productId = mt_rand(1, $countProducts);
            $commentText = $this->nf->quote($this->generateRandomString(200));
            $values .= "($productId, $userId, $commentText),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO comment (product_id, user_id, comment_text)
                    VALUES " . substr($values, 0, -2) . "
                    ON CONFLICT DO NOTHING
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO comment (product_id, user_id, comment_text)
                    VALUES " . substr($values, 0, -2) . "
                    ON CONFLICT DO NOTHING
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
    }

    private function insertProductStars(OutputInterface $output)
    {
        $countUsers = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM \"user\"")->fetch()["count_rows"];
        $countProducts = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM product")->fetch()["count_rows"];

        $values = "";
        $i = 0;
        while ($i < self::STARS_NUMBER)
        {
            $productId = mt_rand(1, $countProducts);
            $userId = mt_rand(1, $countUsers);
            $stars = mt_rand(0, 10);
            $values .= "($productId, $userId, $stars),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO product_stars (product_id, user_id, stars) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO product_stars (product_id, user_id, stars) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
    }

    private function insertProductSold(OutputInterface $output)
    {
        $countUsers = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM \"user\"")->fetch()["count_rows"];
        $countProducts = $this->nf->executeQuery("SELECT COUNT(*) AS count_rows FROM product")->fetch()["count_rows"];

        $values = "";
        $i = 0;
        while ($i < self::SOLD_NUMBER)
        {
            $productId = mt_rand(1, $countProducts);
            $userId = mt_rand(1, $countUsers);
            $quantity = mt_rand(1, 20);
            $values .= "($productId, $userId, $quantity),\n";

            if ($i % self::PACKAGE_SIZE == 0) {
                $query = "
                    INSERT INTO product_sold (product_id, user_id, quantity) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
                $this->nf->executeQuery($query);
                $values = "";
            }

            $i++;
        }
        if (!empty($values)) {
            $query = "
                    INSERT INTO product_sold (product_id, user_id, quantity) 
                    VALUES " . substr($values, 0, -2) . "
                    ;
                ";
            $this->nf->executeQuery($query);
            $values = "";
        }
    }

    private function generateRandomString($length) : string
    {
        $characters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $charactersLength = strlen($characters);
        $randomString = "";
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function generateRandomDate($startData, $endDate) : string
    {
        $min = strtotime($startData);
        $max = strtotime($endDate);

        $val = rand($min, $max);

        return date("Y-m-d H:i:s", $val);
    }

}

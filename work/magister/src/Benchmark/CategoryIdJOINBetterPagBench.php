<?php


namespace App\Benchmark;


class CategoryIdJOINBetterPagBench extends AbstractBenchmark
{

    private const CATEGORY_ID = 1;

    private const PER_PAGE = 100;

    private int $lastProductId = 0;

    public function getRows3NF(): array
    {
        $results = $this->nf->executeQuery("
            SELECT
                p.product_id AS \"product_id\",
                p.product_name AS \"product_name\",
                p.product_tax AS \"product_tax\",
                MIN(sp.product_gross_price)::DECIMAL(10,2) AS \"product_gross_price\",
                MIN(p.product_created) AS \"product_created\",
                MIN(b.brand_id) AS \"brand_id\",
                MIN(b.brand_name) AS \"brand_name\",
                COUNT(DISTINCT cm.comment_id) AS comment_count,
                COUNT(DISTINCT p_stars.ps_id) AS stars_count,
                AVG(p_stars.stars)::DECIMAL(10, 2) AS stars_avg_mark,
                (SELECT SUM(quantity) FROM product_sold WHERE product_sold.product_id = p.product_id) AS sold_count
            FROM product p
            LEFT JOIN brand b ON b.brand_id = p.brand_id
            LEFT JOIN comment cm ON cm.product_id = p.product_id
            LEFT JOIN supplier_product sp ON sp.product_id = p.product_id
            LEFT JOIN product_sold p_sold ON p.product_id = p_sold.product_id
            LEFT JOIN product_stars p_stars ON p.product_id = p_stars.product_id
            WHERE 
                p.category_id = " . self::CATEGORY_ID . "
                AND p.product_id > " . $this->lastProductId . "
            GROUP BY p.product_id
            LIMIT " . self::PER_PAGE . "
            ;
        ")->fetchAll();

        return $results;
    }

    public function getRowsNon3NF(): array
    {
        $results = $this->non3nf->executeQuery("
            SELECT
                product_id,
                product_name,
                product_tax,
                product_gross_price,
                product_created,
                brand_id,
                brand_name,
                comment_count,
                stars_count,
                stars_avg_mark,
                sold_count
            FROM non3nf
            WHERE 
                category_id = " . self::CATEGORY_ID . "
                AND product_id > " . $this->lastProductId . "
            ORDER BY product_id
            LIMIT " . self::PER_PAGE . " 
            ;
        ")->fetchAll();

        $lastProduct = end($results);
        if ($lastProduct) {
            $this->lastProductId = $lastProduct["product_id"];
        }

        return $results;
    }

    public function getBenchmarkDescription(): string
    {
        return "Wszystkie produkty z kategorii o id " . self::CATEGORY_ID . " (wariant z JOIN zamiast podzapytan) + zoptymalizowane stronicowanie (" . self::PER_PAGE . " na strone)";
    }

}
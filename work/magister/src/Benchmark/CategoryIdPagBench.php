<?php


namespace App\Benchmark;


class CategoryIdPagBench extends AbstractBenchmark
{

    private const CATEGORY_ID = 1;

    private const PER_PAGE = 100;

    private int $lastPage = 0;

    public function getRows3NF(): array
    {
        $results = $this->nf->executeQuery("
            SELECT
                p.product_id AS \"product_id\",
                p.product_name AS \"product_name\",
                p.product_tax AS \"product_tax\",
                (SELECT MIN(product_gross_price) FROM supplier_product WHERE supplier_product.product_id = p.product_id)::DECIMAL(10,2) AS \"product_gross_price\",
                p.product_created AS \"product_created\",
                b.brand_id AS \"brand_id\",
                b.brand_name AS \"brand_name\",
                (SELECT COUNT(DISTINCT comment_id) FROM comment WHERE comment.product_id = p.product_id) AS comment_count,
                (SELECT COUNT(DISTINCT ps_id) FROM product_stars WHERE product_stars.product_id = p.product_id) AS stars_count,
                (SELECT AVG(stars) FROM product_stars WHERE product_stars.product_id = p.product_id)::DECIMAL(10,2) AS stars_avg_mark,
                (SELECT SUM(quantity) FROM product_sold WHERE product_sold.product_id = p.product_id) AS sold_count
            FROM product p
            LEFT JOIN brand b ON b.brand_id = p.brand_id
            WHERE p.category_id = " . self::CATEGORY_ID . "
            ORDER BY p.product_id
            LIMIT " . self::PER_PAGE . " OFFSET " . self::PER_PAGE * $this->lastPage . "
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
            WHERE category_id = " . self::CATEGORY_ID . "
            ORDER BY product_id
            LIMIT " . self::PER_PAGE . " OFFSET " . self::PER_PAGE * $this->lastPage . "
            ;
        ")->fetchAll();

        return $results;
    }

    public function afterOneIteration(): void
    {
        $this->lastPage++;
    }

    public function getBenchmarkDescription(): string
    {
        return "Wszystkie produkty z kategorii o id " . self::CATEGORY_ID . " + stronicowanie (" . self::PER_PAGE . " na strone)";
    }

}

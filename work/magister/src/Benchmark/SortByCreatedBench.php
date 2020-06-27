<?php


namespace App\Benchmark;


class SortByCreatedBench extends AbstractBenchmark
{

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
            ORDER BY product_created DESC, p.product_id
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
            ORDER BY product_created DESC, product_id
            ;
        ")->fetchAll();

        return $results;
    }

    public function getBenchmarkDescription(): string
    {
        return "Wszystkie produkty, posortowane po dacie utworzenia (najnowsze na poczatku)";
    }

}
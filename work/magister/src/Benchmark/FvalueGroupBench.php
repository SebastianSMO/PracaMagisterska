<?php


namespace App\Benchmark;


class FvalueGroupBench extends AbstractBenchmark
{

    public function getRows3NF(): array
    {
        $results = $this->nf->executeQuery("
            SELECT
                fvalue_id,
                COUNT(*) AS how_many_products_have_this_fvalue
            FROM product_fvalue
            GROUP BY fvalue_id
            ORDER BY fvalue_id
            ;
        ")->fetchAll();

        return $results;
    }

    public function getRowsNon3NF(): array
    {
        $results = $this->non3nf->executeQuery("
            SELECT
                UNNEST(fvalue_ids) AS fvalue_id,
                COUNT(*) AS how_many_products_have_this_fvalue
            FROM non3nf
            GROUP BY UNNEST(fvalue_ids)
            ORDER BY UNNEST(fvalue_ids)
            ;
        ")->fetchAll();

        unset($results[count($results) - 1]); // ostatni rekord zawiera wyliczenie ile produktow nie ma ani jednej cechy

        return $results;
    }

    public function getBenchmarkDescription(): string
    {
        return "Grupowanie produktow po cechach (ile dana cecha ma produktow. np. 10 produktow ma ceche 'niebieski')";
    }

}

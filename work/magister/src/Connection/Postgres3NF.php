<?php

namespace App\Connection;

use Doctrine\DBAL\Connection;

class Postgres3NF
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection() : Connection
    {
        return $this->connection;
    }
}

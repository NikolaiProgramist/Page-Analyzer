<?php

namespace Page\Analyzer;

use PDO;

class Connect
{
    public function __construct()
    {
    }

    public static function createConnection(): PDO
    {
        $databaseUrl = parse_url($_ENV['DATABASE_URL']);

        $host = $databaseUrl['host'];
        $port = $databaseUrl['port'] ?? 5432;
        $username = $databaseUrl['user'];
        $password = $databaseUrl['pass'];
        $dbname = ltrim($databaseUrl['path'], '/');

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};user={$username};password={$password}";
        $connection = new PDO($dsn);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $connection;
    }
}

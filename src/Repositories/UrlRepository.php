<?php

namespace Page\Analyzer\Repositories;

use PDO;
use Page\Analyzer\DAO\Url;

class UrlRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function create(string $urlName, string $createdAt): int
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':name', $urlName);
        $stmt->bindParam(':created_at', $createdAt);
        $stmt->execute();

        return $this->connection->lastInsertId();
    }

    public function getById(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch();

        if (!$result) {
            return null;
        }

        $url = new Url($result['name']);
        $url->setId($result['id']);
        $url->setCreatedAt($result['created_at']);

        return $url;
    }

    public function getByName(string $name): ?Url
    {
        $sql = "SELECT * FROM urls WHERE name = :name";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        $result = $stmt->fetch();

        if (!$result) {
            return null;
        }

        $url = new Url($result['name']);
        $url->setId($result['id']);
        $url->setCreatedAt($result['created_at']);

        return $url;
    }

    public function getAll(): array
    {
        $sql = "
            SELECT
                urls.id,
                urls.name,
                urls.created_at,
                MAX(url_checks.created_at) AS last_check
            FROM urls
            LEFT JOIN url_checks
                ON urls.id = url_checks.url_id
            GROUP BY urls.id
            ORDER BY urls.id ASC
        ";
        $result = $this->connection->query($sql)->fetchAll();
        $urls = [];

        foreach ($result as $row) {
            $url = new Url($row['name']);
            $url->setId($row['id']);
            $url->setCreatedAt($row['created_at']);
            $url->setLastCheck($row['last_check']);

            $checkRepository = new CheckRepository($this->connection);
            $lastStatusCode = $checkRepository->getLastStatusCode($row['id']);
            $url->setLastStatusCode($lastStatusCode);

            $urls[] = $url;
        }

        return $urls;
    }
}

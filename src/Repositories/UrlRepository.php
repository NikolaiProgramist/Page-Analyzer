<?php

namespace Page\Analyzer\Repositories;

use PDO;
use Page\Analyzer\DAO\Url;
use Page\Analyzer\DAO\Check;

class UrlRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function getUrlById(int $id): ?Url
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

    public function getUrlByName(string $name): ?Url
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

    public function getUrls(): array
    {
        $sql = "
            SELECT
                urls.id,
                urls.name,
                urls.created_at,
                MAX(url_checks.created_at) AS last_check
            FROM urls
            INNER JOIN url_checks
                ON urls.id = url_checks.url_id
            GROUP BY urls.id
            ORDER BY urls.id ASC
        ";
        $result = $this->connection->query($sql);
        $urls = [];

        while ($row = $result->fetch()) {
            $url = new Url($row['name']);
            $url->setId($row['id']);
            $url->setCreatedAt($row['created_at']);
            $url->setLastCheck($row['last_check']);

            $urls[] = $url;
        }

        return $urls;
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

    public function check(int $id, string $createdAt): void
    {
        $sql = "INSERT INTO url_checks (url_id, created_at) VALUES (:url_id, :created_at)";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':url_id', $id);
        $stmt->bindParam(':created_at', $createdAt);
        $stmt->execute();
    }

    public function getChecks(int $urlId): array
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':url_id', $urlId);
        $stmt->execute();
        $checks = [];

        while ($row = $stmt->fetch()) {
            $check = new Check($urlId);
            $check->setId($row['id']);
//            $check->setStatus($row['status']);
//            $check->setH1($row['h1']);
//            $check->setTitle($row['title']);
//            $check->setDescription($row['description']);
            $check->setCreatedAt($row['created_at']);

            $checks[] = $check;
        }

        return $checks;
    }
}

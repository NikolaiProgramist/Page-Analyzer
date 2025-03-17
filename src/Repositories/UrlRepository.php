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
        $sql = "SELECT * FROM urls ORDER BY id DESC";
        $result = $this->connection->query($sql);
        $urls = [];

        while ($row = $result->fetch()) {
            $url = new Url($row['name']);
            $url->setId($row['id']);
            $url->setCreatedAt($row['created_at']);

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
}

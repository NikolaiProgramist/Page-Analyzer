<?php

namespace Hexlet\Code\repositories;

use PDO;
use Hexlet\Code\DAO\Url;

class UrlRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function getUrl(int $id): Url
    {
        $sql = "SELECT * FROM urls WHERE id = :id";
        $result = $this->connection->prepare($sql)->execute([':id' => $id])->fetch();

        $url = new Url($result['name']);
        $url->setId($result['id']);
        $url->setCreatedAt($result['created_at']);

        return $url;
    }

    public function getUrls(): array
    {
        $sql = "SELECT * FROM urls";
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

    public function create(array $url): int
    {
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':name', $url['name']);
        $stmt->bindParam(':created_at', $url['created_at']);
        $stmt->execute();

        return $this->connection->lastInsertId();
    }
}

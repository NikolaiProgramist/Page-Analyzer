<?php

namespace Page\Analyzer\Repositories;

use PDO;
use Page\Analyzer\DAO\Check;

class CheckRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function create(int $id, string $createdAt): void
    {
        $sql = "INSERT INTO url_checks (url_id, created_at) VALUES (:url_id, :created_at)";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':url_id', $id);
        $stmt->bindParam(':created_at', $createdAt);
        $stmt->execute();
    }

    public function getByUrlId(int $urlId): array
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
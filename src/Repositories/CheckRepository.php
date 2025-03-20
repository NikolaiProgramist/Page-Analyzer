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

    public function create(
        int $id,
        int $status,
        ?string $h1,
        ?string $title,
        ?string $description,
        string $createdAt
    ): void {
        $sql = "
            INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at) VALUES
            (:url_id, :status_code, :h1, :title, :description, :created_at)
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':url_id' => $id,
            ':status_code' => $status,
            ':h1' => $h1,
            ':title' => $title,
            ':description' => $description,
            ':created_at' => $createdAt
        ]);
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
            $check->setStatusCode($row['status_code']);
            $check->setH1($row['h1']);
            $check->setTitle($row['title']);
            $check->setDescription($row['description']);
            $check->setCreatedAt($row['created_at']);

            $checks[] = $check;
        }

        return $checks;
    }

    public function getLastStatusCode(int $id): ?int
    {
        $sql = "SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC LIMIT 1";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam('url_id', $id);
        $stmt->execute();

        return $stmt->fetch()['status_code'] ?? null;
    }
}

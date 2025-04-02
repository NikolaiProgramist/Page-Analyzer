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

    public function create(string $logo, string $urlName, string $createdAt): int
    {
        $sql = "INSERT INTO urls (logo, name, created_at) VALUES (:logo, :name, :created_at)";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindParam(':logo', $logo);
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
        $url->setLogo($result['logo']);
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
        $url->setLogo($result['logo']);
        $url->setCreatedAt($result['created_at']);

        return $url;
    }

    public function getAll(): array
    {
        $sql = "SELECT id, logo, name, created_at FROM urls ORDER BY id ASC";
        return $this->connection->query($sql)->fetchAll();
    }
}

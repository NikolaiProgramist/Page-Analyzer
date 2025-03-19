<?php

namespace Page\Analyzer\DAO;

class Url
{
    private int $id;
    private string $name;
    private string $createdAt;
    private ?string $lastCheck;
    private ?int $lastStatusCode;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setLastCheck(?string $lastCheck): void
    {
        $this->lastCheck = $lastCheck;
    }

    public function setLastStatusCode(?int $lastStatusCode): void
    {
        $this->lastStatusCode = $lastStatusCode;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getLastCheck(): ?string
    {
        return $this->lastCheck;
    }

    public function getLastStatusCode(): ?int
    {
        return $this->lastStatusCode;
    }
}

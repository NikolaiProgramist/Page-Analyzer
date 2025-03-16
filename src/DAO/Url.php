<?php

namespace Hexlet\Code\DAO;

class Url
{
    private int $id;
    private string $url;
    private string $created_at;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }
}

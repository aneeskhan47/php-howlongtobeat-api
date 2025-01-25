<?php

namespace AneesKhan47\HowLongToBeat\Models;

class SearchResult
{
    /** @var Game[] */
    public array $games;
    private int $currentPage;
    private int $perPage;
    private int $total;

    public function __construct(int $currentPage, int $perPage, int $total, array $games = [])
    {
        $this->games = $games;
        $this->currentPage = $currentPage;
        $this->perPage = $perPage;
        $this->total = $total;
    }

    public function items(): array
    {
        return $this->games;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function lastPage(): int
    {
        return ceil($this->total / $this->perPage);
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    public function hasPages(): bool
    {
        return $this->lastPage() > 1;
    }

    public function count(): int
    {
        return count($this->games);
    }

    public function isEmpty(): bool
    {
        return empty($this->games);
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }
}

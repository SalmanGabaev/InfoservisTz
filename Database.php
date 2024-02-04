<?php

namespace FpDbTest;

use Exception;
use FpDbTest\UseCase\QueryFormatterUseCase;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        if (empty($args)) {
            return $query;
        }

        $placeholders = preg_match_all('/\?(.)/u', $query, $matches, PREG_SET_ORDER);
        if (!$placeholders) {
            return $query;
        }

        try {
            return QueryFormatterUseCase::execute(query: $query, args: $args, skip: $this->skip());
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function skip(): string
    {
        return 'test';
    }
}

<?php

namespace Os\Framework\DataAbstractionLayer\Driver;

interface DriverInterface
{
    public const TYPE_MYSQL = "mysql";

    public function connect(string $type, string $server, string $database, string $username, string $password): static;
    public function select(string $from, string $fields = "*", string $where = null, array $parameters = [], int $limit = 100): array;
    public function update(string $table, array $updateValues, string $where);
    public function create(string $table, array $values);
    public function execute(string $sql, array $values = []);
}
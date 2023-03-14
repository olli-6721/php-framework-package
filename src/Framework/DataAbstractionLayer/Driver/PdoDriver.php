<?php

namespace Os\Framework\DataAbstractionLayer\Driver;

use Os\Framework\DataAbstractionLayer\Exception\DatabaseStatementExecutionFailed;
use Os\Framework\Debug\Dumper;

class PdoDriver implements DriverInterface
{
    protected \PDO $connection;

    public function connect(string $type, string $server, string $database, string $username, string $password): static
    {
        $this->connection = new \PDO(sprintf("%s:host=%s;dbname=%s", $type, $server, $database), $username, $password);
        return $this;
    }

    /**
     * @throws DatabaseStatementExecutionFailed
     */
    public function select(string $from, string $fields = "*", string $where = null, array $parameters = [], ?int $limit = 100): array
    {
        $query = sprintf("SELECT %s FROM %s", $fields, $from);
        if($where !== null){
            $this->addWhere($query, $where);
        }
        if($limit !== null)
            $query = sprintf("%s LIMIT %d", $query, $limit);
        $statement = $this->connection->prepare($query);
        $exec = $statement->execute($parameters);
        if($exec === false)
            throw new DatabaseStatementExecutionFailed($query);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @throws DatabaseStatementExecutionFailed
     */
    public function update(string $table, array $updateValues, string $where)
    {
        $sql = sprintf("UPDATE %s SET", $table);
        $last = self::toSnakeCase(array_key_last($updateValues));
        foreach($updateValues as $key => $value){
            $sql = sprintf("%s %s = :%s", $sql, self::toSnakeCase($key), $key);
            if($last !== $key){
                $sql = sprintf("%s,", $sql);
            }
        }
        $this->addWhere($sql, $where);
        $statement = $this->connection->prepare($sql);
        $exec = $statement->execute($updateValues);
        if($exec === false)
            throw new DatabaseStatementExecutionFailed($sql);
    }

    /**
     * @throws DatabaseStatementExecutionFailed
     */
    public function create(string $table, array $values)
    {
        $sql = sprintf("INSERT INTO %s (", $table);
        $first = array_key_first($values);
        foreach(array_keys($values) as $key){
            if($first === $key){
                $sql = sprintf("%s%s",$sql, self::toSnakeCase($key));
            }
            else {
                $sql = sprintf("%s,%s",$sql, self::toSnakeCase($key));
            }
        }
        $sql = sprintf("%s) VALUES (", $sql);
        foreach(array_keys($values) as $key){
            if($first === $key){
                $sql = sprintf("%s:%s",$sql, $key);
            }
            else {
                $sql = sprintf("%s,:%s",$sql, $key);
            }
        }
        $sql = sprintf("%s);", $sql);
        $statement = $this->connection->prepare($sql);
        $exec = $statement->execute($values);
        if($exec === false)
            throw new DatabaseStatementExecutionFailed($sql);
    }

    protected function addWhere(string &$sql, string $where){
        if(str_starts_with($where, "WHERE") || str_starts_with($where, "where")){
            $sql = sprintf("%s %s", $sql, $where);
        }
        else {
            $sql = sprintf("%s WHERE %s", $sql, $where);
        }
    }

    public function execute(string $sql, array $values = [])
    {
        $statement = $this->connection->prepare($sql);
        $exec = $statement->execute($values);
        if($exec === false)
            throw new DatabaseStatementExecutionFailed($sql);
    }

    public static function toSnakeCase(string $camelCaseString): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $camelCaseString));
    }
}
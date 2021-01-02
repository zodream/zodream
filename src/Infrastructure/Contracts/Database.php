<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;


interface Database {

    public function addPrefix(string $table): string;
    public function changedSchema(string $schema): Database;

    public function transaction($cb);

    public function insert(string $sql, array $parameters = []);
    public function insertBatch(string $sql, array $parameters = []);
    public function update(string $sql, array $parameters = []): int;
    public function updateBatch(string $sql, array $parameters = []);
    public function delete(string $sql, array $parameters = []): int;
    public function execute(string $sql, array $parameters = []);
    public function executeScalar(string $sql, array $parameters = []);
    public function fetch(string $sql, array $parameters = []);
    public function fetchMultiple(string $sql, array $parameters = []);
    public function first(string $sql, array $parameters = []);

}
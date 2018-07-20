<?php
declare(strict_types = 1);

namespace Zodream\Infrastructure\Http;

class Request {

    public function has(string $key): bool {

    }

    public function get(string $key): mixed {

    }

    public function append(array $data): Request {

        return $this;
    }

    public function all(): array {

    }

    public function uri(): Uri {

    }

    public function server(string $key): mixed {

    }

    public function cookie(string $key): mixed {

    }

    public function header(string $key): string {

    }

    public function argv(string $key): string {

    }

    public function getMethod(): string {

    }
}
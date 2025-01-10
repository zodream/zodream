<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts;

interface Translator {

    public function setLocale(string $locale = ''): static;

    public function getLocale(): string;

    public function load(): void;

    public function isLoaded(): bool;

    /**
     * 判断是否是支持的语言
     * @param string $locale
     * @return bool
     */
    public function isLocale(string $locale): bool;

    public function translate(mixed $message, array $param = [], string|null $name = null): mixed;
}
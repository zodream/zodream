<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Support;


use Zodream\Disk\IStreamWriter;

class StringBuilder implements IStreamWriter {

    public function __construct(
        protected string $content = '',
        protected readonly string $lineSeparator = "\n"
    ) {
    }

    public function append(mixed $value): static {
        if (!is_null($value) && $value !== '') {
            $this->content .= $value;
        }
        return $this;
    }

    public function appendByte(int $value): static {
        return $this->append(chr($value));
    }

    public function appendFormat(string $format, mixed ...$args): static {
        return $this->append(sprintf($format, ...$args));
    }

    public function appendLine(mixed $value = null): static {
        if (is_null($value)) {
            $this->append($this->lineSeparator);
            return $this;
        }
        if (!$this->isAppendLine()) {
            return $this->append($value.$this->lineSeparator);
        }
        return $this->append($this->lineSeparator.$value.$this->lineSeparator);
    }

    public function write(mixed $content): static {
        return $this->append($content);
    }

    public function writeByte(int $byte): static {
        return $this->appendByte($byte);
    }

    protected function isAppendLine(): bool {
        if ($this->content === '') {
            return false;
        }
        if ($this->lineSeparator === '') {
            return false;
        }
        return !str_ends_with($this->content, $this->lineSeparator);
    }

    public function writeLine(mixed $line): static {
        return $this->appendLine($line);
    }

    public function writeLines(array $lines): static {
        if (empty($lines)) {
            return $this;
        }
        foreach ($lines as $line) {
            $this->appendLine($line);
        }
        return $this;
    }

    public function close(): void {
    }

    public function __toString(): string {
        return $this->content;
    }
}
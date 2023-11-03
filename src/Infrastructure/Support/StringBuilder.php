<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Support;


use Zodream\Disk\IStreamWriter;

class StringBuilder implements IStreamWriter {

    public function __construct(
        protected string $content = '',
        protected string $lineSeparator = "\n"
    ) {
    }

    public function write(mixed $content): static {
        $this->content .= $content;
        return $this;
    }

    public function writeByte(int $byte): static {
        return $this->write(chr($byte));
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
        if (!$this->isAppendLine()) {
            return $this->write($line.$this->lineSeparator);
        }
        return $this->write($this->lineSeparator.$line.$this->lineSeparator);
    }

    public function writeLines(array $lines): static {
        if (empty($lines)) {
            return $this;
        }
        if ($this->isAppendLine()) {
            $this->write($this->lineSeparator);
        }
        foreach ($lines as $line) {
            $this->write($line.$this->lineSeparator);
        }
        return $this;
    }

    public function close(): void {
    }

    public function __toString(): string {
        return $this->content;
    }
}
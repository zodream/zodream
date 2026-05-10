<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Support;


use Zodream\Disk\IStreamWriter;

class CodeBuilder implements IStreamWriter {

    public function __construct(
        protected readonly string $indentChar = '    ',
        protected readonly string $lineSeparator = "\n"
    ) {
        $this->handle = fopen('php://memory', 'r+');
    }

    private mixed $handle;
    private int $length = 0;

    public int $indent = 0;

    public function append(mixed $value): static {
        if (!is_null($value) && $value !== '') {
            $res = (string)$value;
            fwrite($this->handle, $res);
            $this->length += strlen($res);
        }
        return $this;
    }

    public function appendByte(int $value): static {
        return $this->append(chr($value));
    }

    public function appendFormat(string $format, mixed ...$args): static {
        return $this->append(sprintf($format, ...$args));
    }

    public function appendLine(mixed $value = null, bool $autoIndent = true): static {
        if (is_null($value)) {
            $this->append($this->lineSeparator);
        } else if (!$this->isAppendLine()) {
            return $this->append($value.$this->lineSeparator);
        } else {
            $this->append($this->lineSeparator.$value.$this->lineSeparator);
        }
        if ($autoIndent) {
            $this->appendIndentCount($this->indent);
        }
        return $this;
    }

    public function write(mixed $content): static {
        return $this->append($content);
    }

    public function writeByte(int $byte): static {
        return $this->appendByte($byte);
    }

    protected function isAppendLine(): bool {
        if ($this->length === 0) {
            return false;
        }
        if ($this->lineSeparator === '') {
            return false;
        }
        return true;
    }

    protected function appendIndentCount(int $indent): static {
        $this->indent = max($indent, 0);
        if ($indent === 0) {
            return $this;
        }
        return $this->append(str_repeat($this->indentChar, $indent));
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

    public function appendIndentLine(): static {
        return $this->appendLine(null, false)->appendIndent();
    }

    public function appendOutdentLine(): static {
        return $this->appendLine(null, false)->appendOutdent();
    }

    public function appendIndent(int $indent = 1): static {
        return $this->appendIndentCount($this->indent + $indent);
    }

    public function appendOutdent(int $outdent = 1): static {
        return $this->appendIndentCount($this->indent - $outdent);
    }

    public function close(): void {
        fclose($this->handle);
    }

    public function __toString(): string {
        return stream_get_contents($this->handle, -1, 0);
    }
}
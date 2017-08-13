<?php
namespace Zodream\Infrastructure\Http\Output;


use Zodream\Disk\Stream;

trait Console {

    protected $stream;

    public function setStream($stream) {
        if ($this->stream instanceof Stream) {
            $this->stream->close();
        }
        $this->stream = new Stream($stream);
        return $this;
    }

    protected function getStream() {
        if (!$this->stream instanceof Stream) {
            $this->stream = new Stream($this->openOutputStream());
        }
        return $this->stream;
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDOUT.
     *
     * @return bool
     */
    protected function hasStdoutSupport() {
        return false === $this->isRunningOS400();
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDERR.
     *
     * @return bool
     */
    protected function hasStderrSupport() {
        return false === $this->isRunningOS400();
    }

    /**
     * Checks if current executing environment is IBM iSeries (OS400), which
     * doesn't properly convert character-encodings between ASCII to EBCDIC.
     *
     * @return bool
     */
    private function isRunningOS400() {
        $checks = array(
            function_exists('php_uname') ? php_uname('s') : '',
            getenv('OSTYPE'),
            PHP_OS,
        );
        return false !== stripos(implode(';', $checks), 'OS400');
    }

    /**
     * @return resource
     */
    public function openOutputStream() {
        if (!$this->hasStdoutSupport()) {
            return fopen('php://output', 'w');
        }

        return @fopen('php://stdout', 'w') ?: fopen('php://output', 'w');
    }

    /**
     * @return resource
     */
    public function openErrorStream() {
        return fopen($this->hasStderrSupport() ? 'php://stderr' : 'php://output', 'w');
    }

    public function writeln($messages) {
        $this->write($messages, true);
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = false) {
        $messages = (array) $messages;
        foreach ($messages as $message) {
            $this->doWrite($message, $newline);
        }
    }

    protected function doWrite($message, $newline) {
        if ($newline) {
            $this->getStream()->writeLine($message);
        } else {
            $this->getStream()->write($message);
        }
        $this->getStream()->flush();
    }
}
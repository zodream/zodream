<?php
declare(strict_types=1);
namespace Zodream\Service\Console;

use Zodream\Disk\File;
use Zodream\Disk\Stream;
use Zodream\Helpers\Json;
use Zodream\Helpers\Str;
use Zodream\Helpers\Xml;
use Zodream\Image\Image;
use Zodream\Infrastructure\Contracts\Http\Output as OutputInterface;
use Zodream\Infrastructure\Contracts\HttpContext as HttpContextInterface;
use Zodream\Infrastructure\Contracts\Response\ExportObject;

class Output implements OutputInterface {

    /**
     * @var Stream|mixed
     */
    protected mixed $stream = null;
    protected int $statusCode = 200;
    /**
     * @var File|ExportObject|Image|array|string
     */
    protected mixed $parameter;

    protected HttpContextInterface $container;

    public function __construct(HttpContextInterface $container) {
        $this->container = $container;
    }

    /**
     * @param array|string|File|Image|ExportObject $parameter
     */
    public function setParameter(mixed $parameter): OutputInterface {
        $this->parameter = $parameter;
        return $this;
    }

    public function send(): bool {
        $this->setStream($this->statusCode === 200 ? $this->openOutputStream() : $this->openErrorStream());
        if ($this->parameter instanceof File) {
            // TODO
        } elseif ($this->parameter instanceof Image) {
            // TODO
        } else {
            $this->writeLine($this->parameter);
        }
        $this->getStream()->close();
        return true;
    }

    public function statusCode(int $code, string $statusText = ''): OutputInterface {
        $this->statusCode = $code;
        return $this;
    }

    public function allowCors(): OutputInterface {
        return $this;
    }

    public function contentType(string $type = 'html', string $option = 'utf-8'): OutputInterface {
        return $this;
    }

    public function header(string $key, $value): OutputInterface {
        return $this;
    }

    public function cookie(string $key, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): OutputInterface {
        return $this;
    }

    public function json($data): OutputInterface {
        return $this->custom(is_array($data) ? Json::encode($data) : $data, 'json');
    }

    public function jsonP($data): OutputInterface {
        return $this->json(
            $this->container->make('request')->get('callback', 'jsonpReturn').
            '('.Json::encode($data).');'
        );
    }

    public function xml($data): OutputInterface {
        return $this->custom(is_array($data) ? Xml::encode($data) : $data, 'xml');
    }

    public function html($data): OutputInterface {
        return $this->custom($data, 'html');
    }

    public function str($data): OutputInterface {
        return $this->custom($data, 'text');
    }

    public function rss($data): OutputInterface {
        return $this->custom($data, 'rss');
    }

    public function file(File $file, int $speed = 512): OutputInterface {
        return $this->setParameter($file);
    }

    public function image(Image $image): OutputInterface {
        return $this->setParameter($image);
    }

    public function custom($data, string $type): OutputInterface {
        return $this->setParameter(Str::value($data));
    }

    public function redirect($url, $time = 0): OutputInterface {
        return $this->setParameter(sprintf('Location: %s,time=%d', $url, $time));
    }

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
    protected function hasStdoutSupport(): bool {
        return false === $this->isRunningOS400();
    }

    /**
     * Returns true if current environment supports writing console output to
     * STDERR.
     *
     * @return bool
     */
    protected function hasStderrSupport(): bool  {
        return false === $this->isRunningOS400();
    }

    /**
     * Checks if current executing environment is IBM iSeries (OS400), which
     * doesn't properly convert character-encodings between ASCII to EBCDIC.
     *
     * @return bool
     */
    private function isRunningOS400(): bool  {
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

    public function writeLine(mixed $messages): void {
        $this->write($messages, true);
    }

    /**
     * {@inheritdoc}
     */
    public function write(mixed $messages, bool $newline = false): void {
        $messages = (array)$messages;
        foreach ($messages as $message) {
            $this->doWrite($message, $newline);
        }
    }

    protected function doWrite(mixed $message, bool $newline): void {
        if ($newline) {
            $this->getStream()->writeLine($message);
        } else {
            $this->getStream()->write($message);
        }
        $this->getStream()->flush();
    }
}
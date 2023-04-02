<?php
declare(strict_types=1);
namespace Zodream\Service\Http;

use Zodream\Disk\File;
use Zodream\Disk\FileException;
use Zodream\Disk\Stream;
use Zodream\Helpers\Arr;
use Zodream\Helpers\Json;
use Zodream\Helpers\Str;
use Zodream\Helpers\Xml;
use Zodream\Http\Header;
use Zodream\Http\Uri;
use Zodream\Image\Image;
use Zodream\Infrastructure\Contracts\Http\HttpOutput;
use Zodream\Infrastructure\Contracts\HttpContext as HttpContextInterface;
use Zodream\Infrastructure\Contracts\Http\Output;
use Zodream\Infrastructure\Contracts\Response\ExportObject;
use Zodream\Infrastructure\Contracts\Response\PreResponse;

class Response implements HttpOutput {

    const STATUS_ITEMS = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect', // 重定向不会把POST 转为GET
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Requests',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Requests Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Requests Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',                      // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    ];

    protected int $statusCode = 200;

    protected string $statusText = 'OK';

    public string $version = '1.1';

    /**
     * @var Header
     */
    public Header $header;

    /**
     * @var File|ExportObject|Image|array|string
     */
    protected mixed $parameter = null;

    protected HttpContextInterface $container;

    public function __construct(HttpContextInterface $container)
    {
        $this->container = $container;
        $this->header = new Header();
        $this->header('Content-Security-Policy', config('safe.csp'));
    }

    public function setParameter(mixed $parameter) {
        $this->parameter = $parameter;
        return $this;
    }

    /**
     * @return array|string|File|Image|ExportObject
     */
    public function getParameter(): mixed {
        return $this->parameter;
    }

    public function send()
    {
        if (is_object($this->parameter) &&
            ($this->parameter instanceof PreResponse
                || method_exists($this->parameter, 'ready'))
        ) {
            $this->parameter->ready($this);
        }
        if (empty($this->parameter)) {
            $this->sendHeaders();
            return true;
        }
        if (!is_string($this->parameter)) {
            $this->sendHeaders()->sendContent();
            return true;
        }
        if (!$this->useGZIP()) {
            $this->sendHeaders()->sendContent();
            return true;
        }
        $callback = 'ob_gzhandler';
        ob_start($callback);
        ob_implicit_flush(false);
        $this->sendHeaders()->sendContent();
        ob_end_flush();
        return true;
    }

    public function statusCode(int $code, string $statusText = ''): Output
    {
        $this->statusCode = $code;
        if ($this->statusCode > 600 || $this->statusCode < 100) {
            throw new \InvalidArgumentException(
                __('The HTTP status code "{code}" is not valid', [
                    'code' => $code
                ])
            );
        }
        $this->statusText = empty($statusText) ? self::STATUS_ITEMS[$code] : $statusText;
        return $this;
    }

    public function contentType(string $type = 'html', string $option = 'utf-8'): Output
    {
        $this->header->setContentType($type, $option);
        return $this;
    }

    public function header(string $key, $value): Output
    {
        $this->header->set($key, $value);
        return $this;
    }

    public function cookie(string $key, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): Output
    {
        $this->header->setCookie($key, $value, $expire, $path, $domain, $secure, $httpOnly);
        return $this;
    }

    public function json($data): Output
    {
        return $this->custom(is_array($data) ? Json::encode($data) : $data, 'json');
    }

    public function jsonP($data): Output
    {
        return $this->json(
            $this->container->make('request')->get('callback', 'jsonpReturn').
            '('.Json::encode($data).');'
        );
    }

    public function xml($data): Output
    {
        return $this->custom( !is_string($data) ? Xml::encode(Arr::toArray($data)) : $data, 'xml');
    }

    public function html($data): Output
    {
        return $this->custom($data, 'html');
    }

    public function str($data): Output
    {
        return $this->custom($data, 'text');
    }

    public function rss($data): Output
    {
        return $this->custom($data, 'rss');
    }

    public function writeLine(mixed $messages) {
        $this->setParameter(Str::value($messages));
    }

    /**
     * 响应内容
     * @param File $file
     * @param int $speed
     * @return Output
     * @throws FileException
     */
    public function file(File $file, int $speed = 512): Output
    {
        $args = [
            'file' => $file,
            'speed' => intval($speed),
            'offset' => 0
        ];
        if (!$file->exist()) {
            throw new FileException($file);
        }
        $length = $file->size();//获取文件大小
        $fileExtension = $file->getExtension();//获取文件扩展名

        $this->header->setCacheControl('public');
        //根据扩展名 指出输出浏览器格式
        switch($fileExtension) {
            case 'exe':
            case 'zip':
            case 'mp3':
            case 'mpg':
            case 'avi':
            case 'ts':
            case 'm3u8':
            case 'mp4':
            case 'flac':
                $this->header->setContentType($fileExtension);
                break;
            default:
                if (!$this->header->has('Content-Type')) {
                    $this->header->setContentType('application/force-download');
                }
                break;
        }
        $this->header->setContentDisposition($file->getName());
        if (empty($speed)) {
            $this->statusCode(200);
            $this->header->setContentLength($length);//输出总长
            return $this->setParameter($file);
        }
        $this->header->setAcceptRanges();
        $range = $this->getRange($length);
        //如果有$_SERVER['HTTP_RANGE']参数
        if(null !== $range) {
            /*   ---------------------------
             Range头域 　　Range头域可以请求实体的一个或者多个子范围。例如， 　　表示头500个字节：bytes=0-499 　

             　表示第二个500字节：bytes=500-999 　　表示最后500个字节：bytes=-500 　　表示500字节以后的范围：

             　bytes=500- 　　第一个和最后一个字节：bytes=0-0,-1 　　同时指定几个范围：bytes=500-600,601-999 　　但是

             　服务器可以忽略此请求头，如果无条件GET包含Range请求头，响应会以状态码206（PartialContent）返回而不是以

             　200 （OK）。
             　---------------------------*/
            // 断点后再次连接 $_SERVER['HTTP_RANGE'] 的值 bytes=4390912-
            $this->statusCode(206);
            $this->header->setContentLength($range['end'] - $range['start']);//输入剩余长度
            $this->header->setContentRange(
                sprintf('%s-%s/%s', $range['start'], $range['end'], $length));
            //设置指针位置
            $args['offset'] = sprintf('%u', $range['start']);
        } else {
            //第一次连接
            $this->statusCode(200);
            $this->header->setContentLength($length);//输出总长
        }
        return $this->setParameter($args);
    }

    public function image(Image $image): Output
    {
        $this->header->setContentType('image', $image->getRealType());
        return $this->setParameter($image);
    }

    public function custom($data, string $type): Output
    {
        $this->contentType($type);
        return $this->setParameter(Str::value($data));
    }

    public function redirect($url, int $time = 0): Output
    {
        $this->statusCode(302);
        $this->header->setRedirect($url, $time);
        return $this;
    }

    /**
     * 响应导出
     * @param ExportObject $expert
     * @return Response
     * @throws \Exception
     */
    public function export(ExportObject $expert) {
        $this->header->setContentType($expert->getType());
        $this->header->setContentDisposition($expert->getName());
        $this->header->setCacheControl('must-revalidate,post-check=0,pre-check=0');
        $this->header->setExpires(0);
        $this->header->setPragma('public');
        return $this->setParameter($expert);
    }

    /**
     * 响应允许cors
     * @return Response
     */
    public function allowCors() {
        $this->header->setCORS();
        if (!empty($this->parameter)) {
            return $this;
        }
        return $this->setParameter('');
    }

    /**
     * 输出其他站点的下载文件
     * @param $uri
     * @param $name
     * @return Response
     * @throws \Exception
     */
    public function fileUrl($uri, $name) {
        $this->header->setContentType('application/save-as')->setContentDisposition($name);
        return $this->setParameter([
            'file' => $uri instanceof Uri ? $uri : new Uri($uri)
        ]);
    }

    /**
     * 基本验证
     * @return $this
     */
    public function basicAuth() {
        $this->statusCode(401);
        $this->header->setWWWAuthenticate(config('app.name'));
        return $this;
    }

    /**
     * 摘要验证
     * @return $this
     */
    public function digestAuth() {
        $this->statusCode(401);
        $name = config('app.name');
        $this->header->setWWWAuthenticate(
            $name,
            'auth',
            Str::random(6),
            md5($name));
        return $this;
    }

    protected function useGZIP(): bool {
        if ($this->container['app']->isDebug()) {
            return false;
        }
        if (defined('APP_GZIP') && !APP_GZIP) {
            return false;
        }
        return extension_loaded('zlib')
            && str_contains($this->container['request']
                ->server('HTTP_ACCEPT_ENCODING', ''), 'gzip');
    }

    /** 获取header range信息
     * @param int $fileSize 文件大小
     * @return array|null
     * @throws \Exception
     */
    protected function getRange(int $fileSize): ?array {
        $range = $this->container->make('request')->server('HTTP_RANGE');
        if (empty($range)) {
            return null;
        }
        $range = preg_replace('/[\s|,].*/', '', $range);
        $range = explode('-', substr($range, 6), 2);
        $range[0] = intval($range[0]);
        $range[1] = isset($range[1]) ? intval($range[1]) : $fileSize;
        if ($range[1] == 0) {
            $range[1] = $fileSize;
        }
        // 直接传整个文件
        if ($range[0] == 0 && $range[1] == $fileSize) {
            return null;
        }
        return [
            'start' => $range[0],
            'end' => $range[1]
        ];
    }

    protected function sendHeaders() {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return $this;
        }

        if (!$this->header->has('Date')) {
            $this->header->setDate(time());
        }

        // headers
        foreach ($this->header as $name => $values) {
            foreach ($values as $value) {
                header($name.': '.$value, false, $this->statusCode);
            }
        }

        // status
        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText),
            true,
            $this->statusCode);

        // cookies
        foreach ($this->header->getCookies() as $cookie) {
            setcookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly()
            );
        }

        return $this;
    }

    protected function sendContent() {
        if ($this->parameter instanceof Image) {
            $this->parameter->saveAs();
            return $this;
        }
        if ($this->parameter instanceof File) {
            readfile((string)$this->parameter);
            return $this;
        }
        if ($this->parameter instanceof ExportObject) {
            $this->parameter->send();
            return $this;
        }
        if (is_array($this->parameter) && $this->parameter['file'] instanceof Uri) {
            readfile((string)$this->parameter['file']);
            return $this;
        }
        if (is_array($this->parameter) && $this->parameter['file'] instanceof File) {
            $stream = new Stream($this->parameter['file']);;
            $stream->open('rb');
            $stream->move(intval($this->parameter['offset']));
            //虚幻输出
            while(!$stream->isEnd()){
                //设置文件最长执行时间
                set_time_limit(0);
                print $stream->read(intval($this->parameter['speed'] * 1024));//输出文件
                flush();//输出缓冲
                ob_flush();
            }
            $stream->close();
            return $this;
        }
        echo (string)$this->parameter;
        return $this;
    }

    public function __sleep(): array
    {
        return ['statusCode', 'statusText', 'version', 'header', 'parameter'];
    }

    public function __wakeup(): void
    {
        $this->container = app(HttpContextInterface::class);
    }
}


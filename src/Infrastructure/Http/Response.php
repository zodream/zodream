<?php
namespace Zodream\Infrastructure\Http;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/16
 * Time: 12:55
 */
use Zodream\Domain\Image\Image;
use Zodream\Infrastructure\Disk\File;
use Zodream\Infrastructure\Interfaces\ExpertObject;
use Zodream\Infrastructure\Error\FileException;
use Zodream\Infrastructure\Http\Component\Header;
use Zodream\Infrastructure\ObjectExpand\JsonExpand;
use Zodream\Infrastructure\ObjectExpand\StringExpand;
use Zodream\Infrastructure\ObjectExpand\XmlExpand;
use Zodream\Infrastructure\Http\Component\Uri;
use Zodream\Service\Config;

class Response {

    protected $statusCode = 200;

    protected $statusText = null;

    public $version = '1.1';

    public static $statusTexts = [
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
        307 => 'Temporary Redirect',
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

    /**
     * @var Header
     */
    public $header;

    /**
     * @var File|ExpertObject|Image|array|string
     */
    protected $parameter;

    public function __construct($parameter = null, $statusCode = 200, array $headers = array()) {
        $this->header = new Header();
        $headers['Content-Security-Policy'] = Config::getValue('safe.csp');
        if (defined('DEBUG') && DEBUG) {
            $headers['Access-Control-Allow-Origin'] = '*'; //ajax 跨域
        }
        $this->header->parse($headers);
        $this->setStatusCode($statusCode)
            ->setParameter($parameter);
    }

    public function setParameter($parameter) {
        $this->parameter = $parameter;
        return $this;
    }

    public function setStatusCode($statusCode, $text = null) {
        $this->statusCode = (int) $statusCode;
        if ($this->statusCode > 600 || $this->statusCode < 100) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $statusCode));
        }
        $this->statusText = false === $text ? '' : (null === $text ? self::$statusTexts[$this->statusCode] : $text);
        return $this;
    }
    
    public function sendHeaders() {
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
    
    public function sendContent() {
        if ($this->parameter instanceof Image) {
            $this->parameter->saveAs();
            $this->parameter->close();
            return $this;
        }
        if ($this->parameter instanceof ExpertObject) {
            $this->parameter->send();
            return $this;
        }
        if (is_array($this->parameter) && $this->parameter['file'] instanceof File) {
            $fp = fopen($this->parameter['file']->getFullName(), 'rb');
            fseek($fp, $this->parameter['offset']);
            //虚幻输出
            while(!feof($fp)){
                //设置文件最长执行时间
                set_time_limit(0);
                print(fread($fp, round($this->parameter['speed'] * 1024, 0)));//输出文件
                flush();//输出缓冲
                ob_flush();
            }
            fclose($fp);
            return $this;
        }
        echo $this->parameter;
        return $this;
    }

    /**
     * 发送响应结果
     * @return boolean
     */
    public function send() {
        if (empty($this->parameter)) {
            $this->sendHeaders();
            return true;
        }
        if (!is_string($this->parameter)) {
            $this->sendHeaders()->sendContent();
            return true;
        }
        $callback = null;
        if ((!defined('DEBUG') || !DEBUG) &&
            (!defined('APP_GZIP') || APP_GZIP) &&
            extension_loaded('zlib') && strpos(Request::server('HTTP_ACCEPT_ENCODING', ''), 'gzip') !== FALSE) {
            $callback = 'ob_gzhandler';
        }
        ob_start($callback);
        ob_implicit_flush(FALSE);
        $this->sendHeaders()->sendContent();
        ob_end_flush();
        return true;
    }

    /**
     * SET JSON
     * @param array|string $data
     * @return Response
     */
    public function sendJson($data) {
        $this->header->setContentType('json');
        if (!is_array($data)) {
            return $this->setParameter($data);
        }
        return $this->setParameter(JsonExpand::encode($data));
    }

    /**
     * SET JSONP
     * @param array $data
     * @return Response
     */
    public function sendJsonp(array $data) {
       return $this->sendJson(
           Request::get('callback', 'jsonpReturn').
           '('.JsonExpand::encode($data).');'
       );
    }

    /**
     * SET XML
     * @param array|string $data
     * @return Response
     */
    public function sendXml($data) {
        $this->header->setContentType('xml');
        if (!is_array($data)) {
            return $this->setParameter($data);
        }
        return $this->setParameter(XmlExpand::encode($data));
    }

    /**
     * SEND HTML
     * @param string|callable $data
     * @return Response
     */
    public function sendHtml($data) {
        $this->header->setContentType('html');
        return $this->setParameter(StringExpand::value($data));
    }

    public function sendRss($data) {
        $this->header->setContentType('rss');
        return $this->setParameter(StringExpand::value($data));
    }

    /**
     * SEND FILE
     * @param File $file
     * @param int $speed
     * @return Response
     * @throws FileException
     */
    public function sendFile(File $file, $speed = 512) {
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
                $this->header->setContentType($fileExtension);
                break;
            default:
                $this->header->setContentType('application/force-download');
                break;
        }
        $this->header->setContentDisposition($file->getName());
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
            $this->setStatusCode(206);
            $this->header->setContentLength($range['end']-$range['start']);//输入剩余长度
            $this->header->setContentRange(
                sprintf('%s-%s/%s', $range['start'], $range['end'], $length));
            //设置指针位置
            $args['offset'] = sprintf('%u', $range['start']);
        } else {
            //第一次连接
            $this->setStatusCode(200);
            $this->header->setContentLength($length);//输出总长
        }
        return $this->setParameter($args);
    }

    /** 获取header range信息
     * @param int $fileSize 文件大小
     * @return array|null
     */
    protected function getRange($fileSize){
        $range = Request::server('HTTP_RANGE');
        if (empty($range)) {
            return null;
        }
        $range = preg_replace('/[\s|,].*/', '', $range);
        $range = StringExpand::explode(substr($range, 6), '-', 2, array(0, $fileSize));
        return array(
            'start' => $range[0],
            'end' => $range[1]
        );
    }

    public function sendImage(Image $image) {
        $this->header->setContentType('image', $image->getRealType());
        return $this->setParameter($image);
    }

    public function sendExport(ExpertObject $expert) {
        $this->header->setContentType($expert->getName());
        $this->header->setContentDisposition($expert->getName());
        $this->header->setCacheControl('must-revalidate,post-check=0,pre-check=0');
        $this->header->setExpires(0);
        $this->header->setPragma('public');
        return $this->setParameter($expert);
    }

    /**
     * @param Uri|string $url
     * @param int $time
     * @return $this
     */
    public function sendRedirect($url, $time = 0) {
        $this->header->setRedirect($url, $time);
        return $this;
    }
}
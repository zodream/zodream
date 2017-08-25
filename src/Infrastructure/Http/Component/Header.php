<?php
namespace Zodream\Infrastructure\Http\Component;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/16
 * Time: 17:26
 */
use Traversable;
use IteratorAggregate;
use ArrayIterator;
use Zodream\Infrastructure\Base\ZObject;

class Header extends ZObject implements IteratorAggregate {

    const COOKIES_ARRAY = 'array';

    const COOKIES_FLAT = 'flat';

    protected $headers = array();
    
    protected $cacheControl = array();

    protected $cookies = array();
    /**
     * @var array
     */
    protected $headerNames = array();
    
    public function __construct() {
        if (!isset($this->headers['cache-control'])) {
            $this->set('Cache-Control', '');
        }
    }

    public function setCookie(
        $cookie,
        $value = null,
        $expire = 0,
        $path = '/',
        $domain = null,
        $secure = false,
        $httpOnly = true) {
        if (!$cookie instanceof Cookie) {
            $cookie = new Cookie(
                $cookie,
                $value,
                $expire,
                $path,
                $domain,
                $secure,
                $httpOnly
            );
        }
        $this->cookies[$cookie->getDomain()][$cookie->getPath()][$cookie->getName()] = $cookie;
        return $this;
    }

    public function removeCookie($name, $path = '/', $domain = null) {
        if (null === $path) {
            $path = '/';
        }

        unset($this->cookies[$domain][$path][$name]);

        if (empty($this->cookies[$domain][$path])) {
            unset($this->cookies[$domain][$path]);

            if (empty($this->cookies[$domain])) {
                unset($this->cookies[$domain]);
            }
        }
        return $this;
    }

    /**
     * Returns an array with all cookies.
     *
     * @param string $format
     *
     * @throws \InvalidArgumentException When the $format is invalid
     *
     * @return array
     */
    public function getCookies($format = self::COOKIES_FLAT) {
        if (!in_array($format, array(self::COOKIES_FLAT, self::COOKIES_ARRAY))) {
            throw new \InvalidArgumentException(sprintf('Format "%s" invalid (%s).', $format, implode(', ', array(self::COOKIES_FLAT, self::COOKIES_ARRAY))));
        }

        if (self::COOKIES_ARRAY === $format) {
            return $this->cookies;
        }

        $flattenedCookies = array();
        foreach ($this->cookies as $path) {
            foreach ($path as $cookies) {
                foreach ($cookies as $cookie) {
                    $flattenedCookies[] = $cookie;
                }
            }
        }

        return $flattenedCookies;
    }

    /**
     * Clears a cookie in the browser.
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return Header
     */
    public function clearCookie($name, $path = '/', $domain = null, $secure = false, $httpOnly = true) {
        return $this->setCookie(new Cookie($name, null, 1, $path, $domain, $secure, $httpOnly));
    }

    public function __toString() {
        $cookies = '';
        foreach ($this->getCookies() as $cookie) {
            $cookies .= 'Set-Cookie: '.$cookie."\r\n";
        }

        ksort($this->headerNames);
        if (!$this->headers) {
            return $cookies;
        }

        $max = max(array_map('strlen', array_keys($this->headers))) + 1;
        $content = '';
        ksort($this->headers);
        foreach ($this->headers as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $name.':', $value);
            }
        }

        return $content.$cookies;
    }

    public function all() {
        return $this->headers;
    }

    /**
     * Returns the parameter keys.
     *
     * @return array An array of parameter keys
     */
    public function keys() {
        return array_keys($this->headers);
    }

    public function replace(array $headers = array()) {
        $this->headers = array();
        return $this->add($headers);
    }

    /**
     * Adds new headers the current HTTP headers set.
     *
     * @param array $headers An array of HTTP headers
     * @return $this
     */
    public function add(array $headers) {
        foreach ($headers as $key => $values) {
            $this->set($key, $values);
        }
        return $this;
    }

    public function get($key, $default = null, $first = true) {
        $key = str_replace('_', '-', strtolower($key));

        if (!array_key_exists($key, $this->headers)) {
            if (null === $default) {
                return $first ? null : array();
            }

            return $first ? $default : array($default);
        }

        if ($first) {
            return count($this->headers[$key]) ? $this->headers[$key][0] : $default;
        }

        return $this->headers[$key];
    }

    public function set($key, $values, $replace = true) {
        $key = str_replace('_', '-', strtolower($key));

        $values = array_values((array) $values);

        if (true === $replace || !isset($this->headers[$key])) {
            $this->headers[$key] = $values;
        } else {
            $this->headers[$key] = array_merge($this->headers[$key], $values);
        }

        if ('cache-control' === $key) {
            $this->cacheControl = $this->parseCacheControl($values[0]);
        }
        return $this;
    }

    public function has($key) {
        return array_key_exists(str_replace('_', '-', strtolower($key)), $this->headers);
    }

    public function delete($tag) {
        $tag = str_replace('_', '-', strtolower($tag));
        unset($this->headers[$tag]);
        if ('cache-control' === $tag) {
            $this->cacheControl = array();
        }
        return $this;
    }

    protected function parseCacheControl($header) {
        $cacheControl = array();
        preg_match_all('#([a-zA-Z][a-zA-Z_-]*)\s*(?:=(?:"([^"]*)"|([^ \t",;]*)))?#', $header, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $cacheControl[strtolower($match[1])] = isset($match[3]) ? $match[3] : (isset($match[2]) ? $match[2] : true);
        }

        return $cacheControl;
    }

    public function setRedirect($url, $time = 0) {
        if (empty($url)) {
            return $this;
        }
        if (empty($time)) {
            return $this->set('Location', $url);
        }
        return $this->set('Refresh', $time.';url='.$url);
    }

    public function setXPoweredBy($name = 'PHP/5.6.12') {
        return $this->set('X-Powered-By', $name);
    }

    /**
     * WEB服务器名称
     * @param string $name
     * @return Header
     */
    public function setServer($name = 'Apache') {
        return $this->set('Server', $name);
    }

    public function setContentLanguage($language = 'zh-CN') {
        return $this->set('Content-language', $language);
    }

    /**
     * md5校验值
     * @param string $md5
     * @return Header
     */
    public function setContentMD5($md5) {
        return $this->set('Content-MD5', $md5);
    }

    /**
     * 缓存控制
     * @param string $option 默认禁止缓存
     * @return Header
     */
    public function setCacheControl(
        $option = 'no-cache, no-store, max-age=0, must-revalidate') {
        return $this->set('Cache-Control', $option);
    }

    /**
     * 实现特定指令
     * @param string $option
     * @return Header
     */
    public function setPragma($option) {
        return $this->set('Pragma', $option);
    }

    /**
     * 如果实体不可取，指定时间重试
     * @param integer $time
     * @return Header
     */
    public function setRetryAfter($time) {
        return $this->set('Retry-After', $time);
    }

    /**
     * 原始服务器发出时间
     * @param integer $time
     * @return Header
     */
    public function setDate($time) {
        return $this->set('Date', gmdate('D, d M Y H:i:s', $time).' GMT');
    }

    /**
     * 响应过期的时间
     * @param integer $time
     * @return Header
     */
    public function setExpires($time) {
        return $this->set('Expires', gmdate('D, d M Y H:i:s', $time).' GMT');
    }

    /**
     * 最后修改时间
     * @param integer $time
     * @return Header
     */
    public function setLastModified($time) {
        return $this->set('Last-Modified', gmdate('D, d M Y H:i:s', $time).' GMT');
    }

    /**
     * 大小
     * @param int|string $length
     * @return Header
     */
    public function sendContentLength($length) {
        return $this->set('Content-Length', $length);
    }

    /**
     * 文件流的范围
     * @param integer $length
     * @param string $type
     * @return Header
     */
    public function setContentRange($length, $type = 'bytes') {
        return $this->set('Content-Range', $type.' '.$length);
    }

    /**
     * 下载文件是指定接受的单位
     * @param string $type
     * @return Header
     */
    public function setAcceptRanges($type = 'bytes') {
        return $this->set('Accept-Ranges', $type);
    }

    /**
     * 下载文件的文件名
     * @param string $filename
     * @return Header
     */
    public function setContentDisposition($filename) {
        if (strpos(Request::server('HTTP_USER_AGENT'), 'MSIE') !== false) {     //如果是IE浏览器
            $filename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
        }
        return $this->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    /**
     * 文件传输编码
     * @param string $encoding
     * @return Header
     */
    public function setTransferEncoding($encoding = 'chunked') {
        return $this->set('Transfer-Encoding', $encoding);
    }

    /**
     * 返回内容的MIME类型
     * @param string $type
     * @param string $option
     * @return Header
     */
    public function setContentType($type = 'html', $option = 'utf-8') {
        $type = strtolower($type);
        if ($type == 'image' || $type == 'img') {
            return $this->set('Content-Type', 'image/'.$option);
        }
        static $args = array(
            'ai'    => 'application/postscript',
            'aif'   => 'audio/x-aiff',
            'aifc'  => 'audio/x-aiff',
            'aiff'  => 'audio/x-aiff',
            'atom'  => 'application/atom+xml',
            'avi'   => 'video/x-msvideo',
            'bin'   => 'application/macbinary',
            'bmp'   => 'image/bmp',
            'cpt'   => 'application/mac-compactpro',
            'css'   => 'text/css',
            'csv'   => 'text/x-comma-separated-values',
            'dcr'   => 'application/x-director',
            'dir'   => 'application/x-director',
            'doc'   => 'application/msword',
            'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dvi'   => 'application/x-dvi',
            'dxr'   => 'application/x-director',
            'eml'   => 'message/rfc822',
            'eps'   => 'application/postscript',
            'exe'   => 'application/octet-stream',
            'flv'   => 'video/x-flv',
            'flash' => 'application/x-shockwave-flash',
            'gif'   => 'image/gif',
            'gtar'  => 'application/x-gtar',
            'gz'    => 'application/x-gzip',
            'hqx'   => 'application/mac-binhex40',
            'htm'   => 'text/html',
            'html'  => 'text/html',
            'jpe'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'jpg'   => 'image/jpeg',
            'js'    => 'application/x-javascript',
            'json'  => 'application/json',
            'log'   => 'text/plain',
            'mid'   => 'audio/midi',
            'midi'  => 'audio/midi',
            'mif'   => 'application/vnd.mif',
            'mov'   => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mp2'   => 'audio/mpeg',
            'mp3'   => 'audio/mpeg',
            'mp4'   => 'video/mpeg',
            'mpe'   => 'video/mpeg',
            'mpeg'  => 'video/mpeg',
            'mpg'   => 'video/mpeg',
            'mpga'  => 'audio/mpeg',
            'oda'   => 'application/oda',
            'pdf'   => 'application/pdf',
            'php'   => 'application/x-httpd-php',
            'php3'  => 'application/x-httpd-php',
            'php4'  => 'application/x-httpd-php',
            'phps'  => 'application/x-httpd-php-source',
            'phtml' => 'application/x-httpd-php',
            'png'   => 'image/png',
            'ppt'   => 'application/powerpoint',
            'ps'    => 'application/postscript',
            'psd'   => 'application/x-photoshop',
            'qt'    => 'video/quicktime',
            'ra'    => 'audio/x-realaudio',
            'ram'   => 'audio/x-pn-realaudio',
            'rm'    => 'audio/x-pn-realaudio',
            'rpm'   => 'audio/x-pn-realaudio-plugin',
            'rss'   => 'application/rss+xml',
            'rtf'   => 'text/rtf',
            'rtx'   => 'text/richtext',
            'rv'    => 'video/vnd.rn-realvideo',
            'shtml' => 'text/html',
            'sit'   => 'application/x-stuffit',
            'smi'   => 'application/smil',
            'smil'  => 'application/smil',
            'swf'   => 'application/x-shockwave-flash',
            'tar'   => 'application/x-tar',
            'tgz'   => 'application/x-tar',
            'text'  => 'text/plain',
            'tif'   => 'image/tiff',
            'tiff'  => 'image/tiff',
            'txt'   => 'text/plain',
            'wav'   => 'audio/x-wav',
            'wbxml' => 'application/wbxml',
            'wmlc'  => 'application/wmlc',
            'word'  => 'application/msword',
            'xht'   => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'xl'    => 'application/excel',
            'xls'   => 'application/excel',
            'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml'   => 'text/xml',
            'xsl'   => 'text/xml',
            'zip'   => 'application/x-zip'
        );
        if (!array_key_exists($type, $args)) {
            return $this->set('Content-type', $type);
        }
        $content = $args[$type];
        if (in_array($type, array('html', 'json', 'rss', 'xml'))) {
            $content .= ';charset='.$option;
        }
        return $this->set('Content-Type', $content);
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator() {
        return new ArrayIterator($this->all());
    }

    public function parse($args) {
        return $this->replace($args);
    }

    public function toArray() {
        return $this->all();
    }
}
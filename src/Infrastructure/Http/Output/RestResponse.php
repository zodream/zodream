<?php
namespace Zodream\Infrastructure\Http\Output;


use Zodream\Helpers\Json;
use Zodream\Helpers\Str;
use Zodream\Helpers\Xml;
use Zodream\Infrastructure\Http\Response;
use Zodream\Infrastructure\Interfaces\IPreResponse;

class RestResponse implements IPreResponse {

    const TYPE_JSON = 0;

    const TYPE_XML = 1;

    const TYPE_JSON_P = 2;

    protected $type = self::TYPE_JSON;

    protected $data;

    public function __construct($data, $type = self::TYPE_JSON) {
        $this->setType($type)->setData($data);
    }

    /**
     * @param int $type
     * @return RestResponse
     */
    public function setType($type) {
        $this->type = self::converterType($type);
        return $this;
    }

    /**
     * @return int
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param mixed $data
     * @return RestResponse
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param Response $response
     * @throws \Exception
     */
    public function ready(Response $response) {
        if ($this->type == self::TYPE_XML) {
            $response->xml($this->formatXml($this->data));
            return;
        }
        if ($this->type == self::TYPE_JSON_P) {
            $response->jsonp($this->data);
            return;
        }
        $response->json($this->data);
    }

    public function text() {
        if ($this->type == self::TYPE_XML) {
            return $this->formatXml($this->data);
        }
        return Json::encode($this->data);
    }

    public function formatXml($data) {
        if (!is_array($data)) {
            return $data;
        }
        $count = count(array_filter(array_keys($data), 'is_numeric'));
        // 数字不能作为xml的标签
        if ($count > 0) {
            $data = compact('data');
        }
        return Xml::specialEncode($data);
    }

    /**
     * @param $data
     * @param int $type
     * @return RestResponse
     */
    public static function create($data, $type = self::TYPE_JSON) {
        return new static($data, $type);
    }

    /**
     * @param $data
     * @return RestResponse
     * @throws \Exception
     */
    public static function createWithAuto($data) {
        return static::create($data, self::formatType());
    }

    /**
     * 转化成当前可用类型
     * @param $format
     * @return int
     */
    public static function converterType($format) {
        $format = is_numeric($format) ? intval($format) : strtolower($format);
        if ($format == 'xml' || $format === self::TYPE_XML) {
            return self::TYPE_XML;
        }
        if ($format == 'jsonp' || $format === self::TYPE_JSON_P) {
            return self::TYPE_JSON_P;
        }
        return self::TYPE_JSON;
    }

    /**
     * 获取内容类型
     * @return string
     * @throws \Exception
     */
    public static function formatType() {
        $format = app('request')->get('format');
        if (!empty($format)) {
            return strtolower($format);
        }
        $accept = app('request')->header('ACCEPT');
        if (empty($accept)) {
            return 'json';
        }
        $args = explode(';', $accept);
        if (Str::contains($args[0], ['/jsonp', '+jsonp'])) {
            return 'jsonp';
        }
        if (Str::contains($args[0], ['/xml', '+xml'])) {
            return 'xml';
        }
        return 'json';
    }

    public function __toString() {
        return $this->text();
    }
}
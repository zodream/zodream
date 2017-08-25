<?php
namespace Zodream\Infrastructure\ObjectExpand;

use Zodream\Infrastructure\ObjectExpand\Xml\ArrayToXml;
use Zodream\Infrastructure\ObjectExpand\Xml\XmlToArray;

class XmlExpand {
    /**
     * @param string $xml
     * @param bool $isArray
     * @return array|object
     * @throws Xml\Exception
     */
    public static function decode($xml, $isArray = true) {
        if (!is_string($xml)) {
            return $xml;
        }
        if ($isArray === false) {
            return simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        return XmlToArray::createArray($xml);
    }

    public static function specialDecode($xml) {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)),TRUE);
    }

    /**
     * @param array $args
     * @param string $root
     * @return string
     */
    public static function encode(array $args, $root = 'root') {
        return ArrayToXml::createXML($root, $args)->saveXML();
    }

    /**
     * 特殊的xml 编码 主要用于微信回复
     * @param array $args
     * @param string $root
     * @return string
     */
    public static function specialEncode(array $args, $root = 'xml') {
        return ArrayToXml::createXML($root, static::toSpecialArray($args))->saveXML();
    }

    /**
     *
     * 转化成标准Xml数组
     * @param string $data
     * @return array
     */
    protected static function toSpecialArray($data) {
        if (is_integer($data)) {
            return $data;
        }
        if (is_object($data)) {
            $data = (array)$data;
        }
        if (!is_array($data)) {
            return [
                '@cdata' => $data
            ];
        }
        foreach ($data as $key => &$item) {
            if (strpos($key, '@') === 0) {
                continue;
            }
            $item = static::toSpecialArray($item);
        }
        return $data;
    }
}
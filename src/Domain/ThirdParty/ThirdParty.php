<?php
namespace Zodream\Domain\ThirdParty;
/**
 * 第三方接口
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/13
 * Time: 11:44
 */
use Zodream\Domain\Filter\Filters\RequiredFilter;
use Zodream\Infrastructure\Base\MagicObject;
use Zodream\Service\Config;
use Zodream\Domain\Support\Http;
use Zodream\Infrastructure\ObjectExpand\JsonExpand;
use Zodream\Infrastructure\ObjectExpand\XmlExpand;
use Zodream\Infrastructure\Http\Component\Uri;
use Zodream\Service\Factory;

abstract class ThirdParty extends MagicObject {
    /**
     * KEY IN CONFIG
     * @var string
     */
    protected $configKey;

    const GET = 'GET';
    const POST = 'POST';

    /**
     *
     * ['url']
     * ['url', ['a', '#b', 'c' => 'd']]
     * [[url, ['a'], true], ['b'], 'post']
     * @var array
     */
    protected $apiMap = array();

    /**
     * @var Http
     */
    protected $http;

    public function __construct($config = array()) {
        $this->http = new Http();
        if (empty($config)) {
            $this->set(Config::getValue($this->configKey));
            return;
        }
        if (array_key_exists($this->configKey, $config)
            && is_array($config[$this->configKey])) {
            $this->set($config[$this->configKey]);
            return;
        }
        $this->set($config);
    }

    /**
     * GET NAME
     * @return string
     */
    public function getName() {
        return $this->configKey;
    }

    /**
     * GET MAP BY NAME
     * @param string $name
     * @return array
     */
    public function getMap($name) {
        if (!array_key_exists($name, $this->apiMap)){
            throw new \InvalidArgumentException('API NOT EXIST!');
        }
        return $this->apiMap[$name];
    }

    protected function httpGet($url) {
        $args = $this->http
            ->setUrl($url)
            ->request()
            ->get();
        Factory::log()->info(sprintf('HTTP GET %s => %s', $url, $args));
        return $args;
    }

    protected function httpPost($url, $data) {
        $args = $this->http->setUrl($url)
            ->request()
            ->setCommonOption()
            ->post($data);
        Factory::log()->info(sprintf('HTTP POST %s + %s => %s', $url,
            is_array($data) ? JsonExpand::encode($data) : $data, $args));
        return $args;
    }

    /**
     * @param string $name
     * @param array $args
     * @return mixed|null|string
     */
    protected function getByApi($name, $args = array()) {
        $args += $this->get();
        $map = $this->getMap($name);
        $url = new Uri();
        if (is_array($map[0])) {
            return $this->httpPost(
                $url->decode($map[0][0])
                    ->addData($this->getData((array)$map[0][1], $args)),
                $this->getPostData($name, $args)
            );
        }
        $url->decode($map[0]);
        if (count($map) < 3 || strtoupper($map[2]) != self::POST) {
            return $this->httpGet($url->addData($this->getData((array)$map[1], $args)));
        }
        return $this->httpPost($url,
            $this->getPostData($name, $args));
    }


    /**
     * GET URL THAT METHOD IS GET
     * @param string $name
     * @param array $args
     * @return Uri
     */
    protected function getUrl($name, array $args = array()) {
        $map = $this->getMap($name);
        $args += $this->get();
        $uri = new Uri();
        if (is_array($map[0])) {
            return $uri->decode($map[0][0])
                ->addData($this->getData((array)$map[0][1], $args));
        }
        $uri->decode($map[0]);
        if (count($map) != 3 || strtoupper($map[2]) != self::POST) {
            $uri->addData($this->getData((array)$map[1], $args));
        }
        return $uri;
    }

    /**
     * GET POST DATA
     * @param string $name
     * @param array $args
     * @return array|string
     * @internal param array $data
     */
    protected function getPostData($name, array $args) {
        $map = $this->getMap($name);
        if (!is_array($map) || count($map) < 2) {
            return array();
        }
        return $this->getData((array)$map[1], $args);
    }

    /**
     * 获取值 根据 #区分必须  $key => $value 区分默认值
     * 支持多选 键必须为 数字， 支持多级 键必须为字符串
     * @param array $keys
     * @param array $args
     * @return array
     */
    protected function getData(array $keys, array $args) {
        $data = array();
        foreach ($keys as $key => $item) {
            $data = array_merge($data,
                $this->getDataByKey($key, $item, $args));
        }
        return $data;
    }

    /**
     * 获取一个值
     * @param $key
     * @param $item
     * @param array $args
     * @return array
     */
    protected function getDataByKey($key, $item, array $args) {
        if (is_array($item)) {
            $item = $this->chooseData($item, $args);
        }
        if (is_integer($key)) {
            if (is_array($item)) {
                return $item;
            }
            $key = $item;
            $item = null;
        }
        $need = false;
        if (strpos($key, '#') === 0) {
            $key = substr($key, 1);
            $need = true;
        }
        $keyTemp = explode(':', $key, 2);
        if (array_key_exists($keyTemp[0], $args)) {
            $item = $args[$keyTemp[0]];
        }
        if ($this->checkEmpty($item)) {
            if ($need) {
                throw  new \InvalidArgumentException($keyTemp[0].' IS NEED!');
            }
            return [];
        }
        if (count($keyTemp) > 1) {
            $key = $keyTemp[1];
        }
        return [$key => $item];
    }

    /**
     * MANY CHOOSE ONE
     * @param array $item
     * @param array $args
     * @return array
     */
    protected function chooseData(array $item, array $args) {
        $data = $this->getData($item, $args);
        if (empty($data)) {
            throw new \InvalidArgumentException('ONE OF MANY IS NEED!');
        }
        return $data;
    }

    protected function getXml($name, $args = array()) {
        return XmlExpand::specialDecode($this->getByApi($name, $args));
    }

    protected function getJson($name, $args = array()) {
        return JsonExpand::decode($this->getByApi($name, $args));
    }

    /**
     * CHECK IS EMPTY
     * @param $value
     * @return bool
     */
    protected function checkEmpty($value) {
        $filter = new RequiredFilter();
        return !$filter->validate($value);
    }

    /**
     * _call
     * 魔术方法，做api调用转发
     * @param string $name    调用的方法名称
     * @param array $arg      参数列表数组
     * @since 5.0
     * @return array          返加调用结果数组
     */
    public function __call($name, $arg) {
        return $this->getByApi($name, isset($arg[0]) ? $arg[0] : array());
    }
}
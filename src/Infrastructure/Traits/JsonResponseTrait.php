<?php
namespace Zodream\Infrastructure\Traits;

use Zodream\Infrastructure\Http\Response;
use Zodream\Infrastructure\Interfaces\ArrayAble;
use Zodream\Service\Factory;

trait JsonResponseTrait {

    /**
     * ajax 返回
     * @param $data
     * @param string $type
     * @return Response
     * @throws \Exception
     */
    public function json($data, $type = 'json') {
        switch (strtolower($type)) {
            case 'xml':
                return Factory::response()->xml($data);
            case 'jsonp':
                return Factory::response()->jsonp($data);
        }
        return Factory::response()->json($data);
    }

    /**
     * ajax 成功返回
     * @param null $data
     * @param null $message
     * @return Response
     */
    public function jsonSuccess($data = null, $message = null) {
        if (!is_array($message)) {
            $message = ['messages' => $message];
        }
        if ($data instanceof ArrayAble) {
            $data = $data->toArray();
        }
        return $this->json(array_merge(array(
            'code' => 200,
            'status' => 'success',
            'data' => $data
        ), $message));
    }

    /**
     * ajax 失败返回
     * @param string|array $message
     * @param int $code
     * @return Response
     */
    public function jsonFailure($message = '', $code = 400) {
        return $this->json(array(
            'code' => $code,
            'status' => 'failure',
            'errors' => $message
        ), $code);
    }
}
<?php
namespace Zodream\Service\Controller;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/11/28
 * Time: 18:22
 */
use Zodream\Infrastructure\Http\Response;
use Zodream\Service\Factory;
use Zodream\Infrastructure\Http\Request;

abstract class RestController extends BaseController  {

    /**
     * @return string
     */
    protected function format() {
        return 'json';
    }

    protected function rules() {
        return [
            'index' => ['GET', 'HEAD'],
            'view' => ['GET', 'HEAD'],
            'create' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'delete' => ['DELETE'],
        ];
    }

    protected function beforeFilter($action) {
        $rules = $this->rules();
        if (!array_key_exists($action, $rules)) {
            return true;
        }
        if (in_array(Request::method(), $rules[$action])) {
            return true;
        }
        return $this->ajaxError('ERROE REQUEST METHOD!');
    }

    /**
     * @param array $data
     * @param int $statusCode
     * @return Response
     */
    protected function ajax($data, $statusCode = 200) {
        Factory::response()->setStatusCode($statusCode);
        switch (strtolower($this->format())) {
            case 'xml':
                return Factory::response()->sendXml($data);
            case 'jsonp':
                return Factory::response()->sendJsonp($data);
            default:
                return Factory::response()->sendJson($data);
        }
    }

    /**
     * ajax 成功返回
     * @param null $data
     * @return Response
     */
    public function ajaxSuccess($data = null) {
        return $this->ajax([
            'code' => 0,
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * ajax 失败返回
     * @param string $message
     * @param int $code
     * @return Response
     */
    public function ajaxFailure($message = '', $code = 1) {
        return $this->ajax(array(
            'code' => $code,
            'status' => 'failure',
            'msg' => $message
        ));
    }
}
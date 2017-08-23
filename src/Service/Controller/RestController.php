<?php
namespace Zodream\Service\Controller;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/11/28
 * Time: 18:22
 */
use Zodream\Infrastructure\Http\Response;
use Zodream\Infrastructure\Traits\JsonResponseTrait;
use Zodream\Service\Factory;
use Zodream\Infrastructure\Http\Request;

abstract class RestController extends BaseController  {

    use JsonResponseTrait;

    protected $canCSRFValidate = false;

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
        return $this->jsonFailure('ERROE REQUEST METHOD!');
    }

    /**
     * @param array $data
     * @param int $statusCode
     * @return Response
     */
    protected function json($data, $statusCode = 200) {
        Factory::response()->setStatusCode($statusCode);
        switch (strtolower($this->format())) {
            case 'xml':
                return Factory::response()->xml($data);
            case 'jsonp':
                return Factory::response()->jsonp($data);
            default:
                return Factory::response()->json($data);
        }
    }
}
<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts\Response;


use Zodream\Html\Page;
use Zodream\Infrastructure\Contracts\Http\Output;

interface JsonResponse {

    /**
     * 输出数据
     * @param $data
     * @return Output
     */
    public function render($data): Output;

    /**
     * 成功返回数据
     * @param $data
     * @param string $message
     * @return Output
     */
    public function renderData($data, string $message = ''): Output;

    /**
     * 成功返回分页数据
     * @param Page $page
     * @return Output
     */
    public function renderPage(Page $page): Output;

    /**
     * 返回失败数据
     * @param string|array $message
     * @param int $code
     * @param int $statusCode
     * @return Output
     */
    public function renderFailure(array|string $message, int $code = 400, int $statusCode = 0): Output;
}
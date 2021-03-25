<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Contracts\Response;
/**
 * 导出文件的接口
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/7/16
 * Time: 18:21
 */
interface ExportObject {

    /**
     * 响应的文件名
     * @return string
     */
    public function getName(): string;

    /**
     * 响应文件类型
     * @return string
     */
    public function getType(): string;
    /**
     * 开始
     * @return mixed
     */
    public function send();
}
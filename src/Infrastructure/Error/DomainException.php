<?php
namespace Zodream\Infrastructure\Error;

use Zodream\Route\Exception\NotFoundHttpException;

/**
 * 当域名不允许时
 * @package Zodream\Infrastructure\Error
 */
class DomainException extends NotFoundHttpException {

}
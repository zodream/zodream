<?php
namespace Zodream\Infrastructure\Support;

use Zodream\Infrastructure\Interfaces\ArrayAble;
use Zodream\Infrastructure\Interfaces\JsonAble;
use Zodream\Validate\MessageBag as BaseBag;

class MessageBag extends BaseBag implements ArrayAble, JsonAble {

}
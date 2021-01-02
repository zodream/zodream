<?php
namespace Zodream\Infrastructure\Support;

use Zodream\Infrastructure\Contracts\ArrayAble;
use Zodream\Infrastructure\Contracts\JsonAble;
use Zodream\Validate\MessageBag as BaseBag;

class MessageBag extends BaseBag implements ArrayAble, JsonAble {

}
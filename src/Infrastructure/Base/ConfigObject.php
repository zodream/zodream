<?php
namespace Zodream\Infrastructure\Base;

/**
 * THIS IS CONFIG TO CREATE OBJECT
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/9/1
 * Time: 14:13
 */
use Zodream\Infrastructure\Concerns\ConfigTrait;

abstract class ConfigObject {

    use ConfigTrait;

    protected array $configs = [];

}
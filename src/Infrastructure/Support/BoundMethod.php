<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Support;

use ArrayAccess;
use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Zodream\Infrastructure\Contracts\Container;

class BoundMethod {

    /**
     * 执行方法
     * @param $callback
     * @param Container $container
     * @param array $parameters
     * @param null $defaultMethod
     * @return mixed
     */
    public static function call($callback, Container $container, array $parameters = [], $defaultMethod = null) {
        if (is_string($callback) && ! $defaultMethod && method_exists($callback, '__invoke')) {
            $defaultMethod = '__invoke';
        }

        if (static::isCallableWithAtSign($callback) || $defaultMethod) {
            return static::callClass($callback, $container, $parameters, $defaultMethod);
        }

        return static::callBoundMethod($container, $callback, function () use ($container, $callback, $parameters) {
            return call_user_func_array(
                $callback, static::getMethodDependencies(static::getCallReflector($callback)->getParameters(), $container, $parameters)
            );
        });
    }

    /**
     * 生成新对象
     * @param $concrete
     * @param Container $container
     * @param array|ArrayAccess $parameters
     * @return mixed|object
     * @throws ReflectionException
     */
    public static function newClass($concrete, Container $container, $parameters = []) {
        if ($concrete instanceof Closure) {
            return static::call($concrete, $container, $parameters);
        }
        if (!class_exists($concrete)) {
            throw new Exception(
                __('Target {concrete} is not instantiable.', compact('concrete'))
            );
        }
        $reflector = new ReflectionClass($concrete);
        if (! $reflector->isInstantiable()) {
            throw new Exception(
                __('Target {concrete} is not instantiable.', compact('concrete'))
            );
        }
        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $concrete;
        }
        $dependencies = $constructor->getParameters();
        $instances = static::getMethodDependencies(
            $dependencies, $container, $parameters
        );
        return $reflector->newInstanceArgs($instances);
    }

    /**
     * @param ReflectionParameter[] $dependencies
     * @param Container $container
     * @param array $parameters
     * @return array
     * @throws ReflectionException
     */
    protected static function getMethodDependencies(array $dependencies, Container $container, array $parameters)
    {
        $items = [];
        foreach ($dependencies as $dependency) {
            $items[] = static::getParameterFromSource($dependency, $container, $parameters);
        }
        return array_merge($items, $parameters);
    }

    protected static function getParameterFromSource(ReflectionParameter $dependency, Container $container, array &$parameters) {
        $name = $dependency->getName();
        if (array_key_exists($name, $parameters)) {
            $res = $parameters[$name];
            unset($parameters[$name]);
            return $res;
        }
        $className = static::getParameterClassName($dependency);
        if (!empty($className)) {
            if (array_key_exists($className, $parameters)) {
                $res = $parameters[$className];
                unset($parameters[$className]);
                return $res;
            }
            return $container->make($className);
        }
        if ($dependency->isDefaultValueAvailable()) {
            return $dependency->getDefaultValue();
        }
        if (!$dependency->isOptional() && empty($parameters)) {
            $message = "Unable to resolve dependency [{$dependency}] in class {$dependency->getDeclaringClass()->getName()}";
            throw new Exception($message);
        }
    }

    public static function getParameterClassName(ReflectionParameter $parameter): string {
        $type = $parameter->getType();
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return '';
        }
        $name = $type->getName();
        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }
            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }
        return $name;
    }

    protected static function callBoundMethod($container, $callback, $default)
    {
        if (! is_array($callback)) {
            return static::unwrapIfClosure($default);
        }

        // Here we need to turn the array callable into a Class@method string we can use to
        // examine the container and see if there are any method bindings for this given
        // method. If there are, we can call this method binding callback immediately.
        $method = static::normalizeMethod($callback);

        if (method_exists($container, 'hasMethodBinding') && $container->hasMethodBinding($method)) {
            return $container->callMethodBinding($method, $callback[0]);
        }

        return static::unwrapIfClosure($default);
    }

    protected static function normalizeMethod($callback)
    {
        $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);

        return "{$class}@{$callback[1]}";
    }

    protected static function unwrapIfClosure($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }

    protected static function callClass($target, Container $container, array $parameters = [], $defaultMethod = null)
    {
        $segments = explode('@', $target);

        // We will assume an @ sign is used to delimit the class name from the method
        // name. We will split on this @ sign and then build a callable array that
        // we can pass right back into the "call" method for dependency binding.
        $method = count($segments) === 2
            ? $segments[1] : $defaultMethod;

        if (is_null($method)) {
            throw new InvalidArgumentException('Method not provided.');
        }

        return static::call(
            [$container->make($segments[0]), $method], $container, $parameters
        );
    }

    protected static function getCallReflector($callback)
    {
        if (is_string($callback) && str_contains($callback, '::')) {
            $callback = explode('::', $callback);
        } elseif (is_object($callback) && ! $callback instanceof Closure) {
            $callback = [$callback, '__invoke'];
        }

        return is_array($callback)
            ? new ReflectionMethod($callback[0], $callback[1])
            : new ReflectionFunction($callback);
    }

    protected static function isCallableWithAtSign($callback)
    {
        return is_string($callback) && str_contains($callback, '@');
    }


}
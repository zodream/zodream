<?php
declare(strict_types=1);

namespace Zodream\Infrastructure\Pipeline;

use Closure;
use RuntimeException;
use Throwable;
use Zodream\Infrastructure\Contracts\Container;

/**
 * Class MiddlewareProcessor
 * @package Zodream\Infrastructure\Pipeline
 */
class MiddlewareProcessor {

    protected $method = 'handle';
    protected $container;
    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function send($passable)
    {
        $this->passable = $passable;
        return $this;
    }

    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();
        return $this;
    }

    public function via($method)
    {
        $this->method = $method;

        return $this;
    }

    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes()), $this->carry(), $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * Run the pipeline and return the result.
     *
     * @return mixed
     */
    public function thenReturn()
    {
        return $this->then(function ($passable) {
            return $passable;
        });
    }

    protected function prepareDestination(Closure $destination)
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (Throwable $e) {
                return $this->handleException($passable, $e);
            }
        };
    }

    protected function carry() {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    if (is_callable($pipe)) {
                        // If the pipe is an instance of a Closure, we will just call it directly but
                        // otherwise we'll resolve the pipes out of the container and call it with
                        // the appropriate method and arguments, returning the results back out.
                        return $pipe($passable, $stack);
                    } elseif (! is_object($pipe)) {
                        list($name, $parameters) = $this->parsePipeString($pipe);

                        // If the pipe is a string we will parse the string and resolve the class out
                        // of the dependency injection container. We can then build a callable and
                        // execute the pipe function giving in the parameters that are required.
                        $pipe= $this->getContainer()->make($name);

                        $parameters = array_merge([$passable, $stack], $parameters);
                    } else {
                        // If the pipe is already an object we'll just make a callable and pass it to
                        // the pipe as-is. There is no need to do any extra parsing and formatting
                        // since the object we're given was already a fully instantiated object.
                        $parameters = [$passable, $stack];
                    }

                    $carry = method_exists($pipe, $this->method)
                        ? $pipe->{$this->method}(...$parameters)
                        : $pipe(...$parameters);

                    return $this->handleCarry($carry);
                } catch (Throwable $e) {
                    return $this->handleException($passable, $e);
                }
            };
        };
    }

    protected function parsePipeString($pipe) {
        list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    protected function pipes()
    {
        return $this->pipes;
    }

    /**
     * Get the container instance.
     *
     * @return Container
     *
     * @throws \RuntimeException
     */
    protected function getContainer()
    {
        if (! $this->container) {
            throw new RuntimeException('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }

    /**
     * Set the container instance.
     *
     * @param  Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Handle the value returned from each pipe before passing it to the next.
     *
     * @param  mixed  $carry
     * @return mixed
     */
    protected function handleCarry($carry)
    {
        return $carry;
    }

    /**
     * Handle the given exception.
     *
     * @param  mixed  $passable
     * @param Throwable $e
     * @return mixed
     *
     * @throws Throwable
     */
    protected function handleException($passable, Throwable $e)
    {
        throw $e;
    }
}
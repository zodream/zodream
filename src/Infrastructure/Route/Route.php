<?php
declare(strict_types = 1);

namespace Zodream\Infrastructure\Route;

use Exception;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Http\Response;

class Route {

    const HTTP_METHODS = ['OPTIONS', 'HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'TRACE', 'CONNECT'];
    /**
     * @var callable
     */
    protected $action;
    /**
     * @var string
     */
    protected $definition;
    /**
     * @var array
     */
    protected $constraints;
    /**
     * @var array
     */
    protected $defaults;
    /**
     * @var array
     */
    protected $methods;
    /**
     * @var array
     */
    protected $parts;
    /**
     * @var string
     */
    protected $regex;
    /**
     * @var array
     */
    protected $paramMap;
    /**
     * @var array
     */
    protected $params;

    /**
     * Class constructor
     *
     * @param string $definition
     * @param callable $action
     * @param array $methods
     * @param array $constraints
     * @param array $defaults
     * @throws Exception
     */
    public function __construct(
        string $definition,
        callable $action,
        array $methods = self::HTTP_METHODS,
        array $constraints = [],
        array $defaults = []) {;
        $this->setAction($action);
        $this->definition = $definition;
        $this->constraints = $constraints;
        $this->defaults = $defaults;
        $this->setMethods($methods);
        $this->parts = $this->parseDefinition($definition);
        $this->regex = $this->buildRegex($this->parts, $constraints);
    }

    public static function factory(array $config): Route {
        if (!isset($config['definition'])) {
            throw new Exception(
                __('Missing "definition" config option.')
            );
        }
        $action = isset($config['action']) ? $config['action'] : $config['definition'];
        $definition = $config['definition'];
        $constraints = isset($config['constraints']) ? $config['constraints'] : [];
        $defaults = isset($config['defaults']) ? $config['defaults'] : [];
        $methods = isset($config['methods']) ? $config['methods'] : true;
        return new static($definition, $action, $methods,  $constraints, $defaults);
    }

    public function setMethods(array $methods): Route {
        $this->methods = array_map('strtoupper', $methods);
        return $this;
    }
    /**
     * Get methods
     *
     * @return array|bool
     */
    public function getMethods() {
        return $this->methods;
    }
    /**
     * Parse definition
     *
     * @param string $definition
     * @return array
     * @throws Exception
     */
    protected function parseDefinition(string $definition): array {
        $pos = 0;
        $length = strlen($definition);
        $parts = [];
        $stack = [&$parts];
        $level = 0;
        while ($pos < $length) {
            preg_match('(\G(?P<literal>[^:{\[\]]*)(?P<token>[:{\[\]]|$))', $definition, $matches, 0, $pos);
            $pos += strlen($matches[0]);
            if (!empty($matches['literal'])) {
                $stack[$level][] = ['literal', $matches['literal']];
            }
            if ($matches['token'] === ':') {
                $pattern = '(\G(?P<name>[^:/{\[\]]+)(?:{(?P<delimiters>[^}]+)})?:?)';
                if (!preg_match($pattern, $definition, $matches, 0, $pos)) {
                    throw new Exception(
                        __('Found empty parameter name')
                    );
                }
                $stack[$level][] = [
                    'parameter',
                    $matches['name'],
                    isset($matches['delimiters']) ? $matches['delimiters'] : null,
                ];
                $pos += strlen($matches[0]);
            } elseif ($matches['token'] === '[') {
                $stack[$level][] = ['optional', []];
                $stack[$level + 1] = &$stack[$level][count($stack[$level]) - 1][1];
                $level++;
            } elseif ($matches['token'] === ']') {
                unset($stack[$level]);
                $level--;
                if ($level < 0) {
                    throw new Exception(
                        __(
                            'Found closing bracket without matching opening bracket'
                        )
                    );
                }
            } else {
                break;
            }
        }
        if ($level > 0) {
            throw new Exception(
                __('Found unbalanced brackets')
            );
        }
        return $parts;
    }

    /**
     * Build regex
     *
     * @param array $parts
     * @param array $constraints
     * @param int $groupIndex
     * @return string
     */
    protected function buildRegex(array $parts, array $constraints, &$groupIndex = 1) {
        $regex = '';
        foreach ($parts as $part) {
            switch ($part[0]) {
                case 'literal':
                    $regex .= preg_quote($part[1]);
                    break;
                case 'parameter':
                    $groupName = '?P<param' . $groupIndex . '>';
                    if (isset($constraints[$part[1]])) {
                        $regex .= '(' . $groupName . $constraints[$part[1]] . ')';
                    } elseif ($part[2] === null) {
                        $regex .= '(' . $groupName . '[^/]+)';
                    } else {
                        $regex .= '(' . $groupName . '[^' . $part[2] . ']+)';
                    }
                    $this->paramMap['param' . $groupIndex++] = $part[1];
                    break;
                case 'optional':
                    $regex .= '(?:' . $this->buildRegex($part[1], $constraints, $groupIndex) . ')?';
                    break;
            }
        }
        return $regex;
    }

    public function setAction(callable $action): Route {
        $this->action = $action;
        return $this;
    }

    public function getAction(): callable {
        return $this->action;
    }
    /**
     * Get definition
     *
     * @return string
     */
    public function getDefinition(): string {
        return $this->definition;
    }
    /**
     * Get constraints
     *
     * @return array
     */
    public function getConstraints(): array {
        return $this->constraints;
    }
    /**
     * Get defaults
     *
     * @return array
     */
    public function getDefaults(): array {
        return $this->defaults;
    }
    /**
     * Get regex
     *
     * @return string
     */
    public function getRegex(): string {
        return $this->regex;
    }

    /**
     * Match
     *
     * @param $path
     * @param int $basePath
     * @return bool
     */
    public function match(string $path, $basePath = null) {
        $regex = '(^' . $this->regex . '$)';
        if ($basePath !== null) {
            $length = strlen($basePath);
            if (substr($path, 0, $length) !== $basePath) {
                return false;
            }
            $path = substr($path, $length);
        }
        $result = (bool) preg_match($regex, $path, $matches, null, (int) $basePath);
        $this->params = null;
        if ($result) {
            $params = [];
            foreach ($matches as $name => $value) {
                if (isset($this->paramMap[$name])) {
                    $params[$this->paramMap[$name]] = $value;
                }
            }
            $this->params = array_merge($this->defaults, $params);
        }
        return $result;
    }

    /**
     * Params
     *
     * @return array|null
     */
    public function params(): array {
        return $this->params;
    }
    /**
     * Allow
     *
     * @param Request $request
     * @return boolean
     */
    public function allow(Request $request): bool {
        return in_array($request->getMethod(), $this->methods);
    }

    public function assemble(array $params = []): string {
        $parts = $this->parts;
        $merged = array_merge($this->defaults, $params);
        $path = $this->buildPath($parts, $merged);
        $this->params = $merged;
        return $path;
    }
    /**
     * Build path
     *
     * @param array $parts
     * @param array $params
     * @param boolean $optional
     * @return string
     * @throws Exception
     */
    protected function buildPath(array $parts, array $params, $optional = false) {
        $path = '';
        foreach ($parts as $part) {
            switch ($part[0]) {
                case 'literal':
                    $path .= $part[1];
                    break;
                case 'parameter':
                    if (!isset($params[$part[1]])) {
                        if (!$optional) {
                            throw new Exception(
                                __('Missing parameter "{name}"', [
                                    'name' => $path[1]
                                ])
                            );
                        }
                        return '';
                    }
                    $path .= rawurlencode($params[$part[1]]);
                    break;
                case 'optional':
                    $segment = $this->buildPath($part[1], $params, true);
                    if ($segment !== '') {
                        $path .= $segment;
                    }
                    break;
            }
        }
        return $path;
    }

    public function handle(Request $request, Response $response) {
        $request->append($this->params());
        return call_user_func($this->action, $request, $response);
    }
}
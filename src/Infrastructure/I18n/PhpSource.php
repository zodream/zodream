<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\I18n;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/25
 * Time: 17:12
 */
class PhpSource extends I18n {


    protected function formatLanguage(string $language): string {
        $language = str_replace('_', '-', strtolower($language));
        if ($this->existLanguage($language)) {
            return $language;
        }
        if (str_starts_with($language, 'en-')) {
            return 'en';
        }
        return 'zh-cn';
    }

    public function translate(mixed $message, array $param = [], ?string $name = null): mixed {
        if (empty($message)) {
            return $message;
        }
        parent::translate($message, $param, $name);
        $args = $this->get($this->fileName, array());
        if (!$this->has($name) || !array_key_exists($message, $args)) {
            return $this->format($message, $param);
        }
        return $this->format($args[$message], $param);
    }


    /**
     * 修改源
     */
    public function reset() {
        if ($this->has($this->fileName)) {
            return;
        }
        $file = $this->directory->childFile($this->language.'/'.$this->fileName.'.php');
        if (!$file->exist()) {
            return;
        }
        $args = include (string)$file;
        if (!is_array($args)) {
            return;
        }
        $this->set($this->fileName, $this->formatArr($args));
    }

    protected function formatArr(array $data, $prefix = '') {
        $args = [];
        foreach ($data as $key => $item) {
            $key = $prefix.$key;
            if (!is_array($item)) {
                $args[$key] = $item;
                continue;
            }
            if ($prefix === '') {
                $args[$key] = $item;
            }
            $item = $this->formatArr($item, $key.'.');
            $args = array_merge($args, $item);
        }
        return $args;
    }

    public function existLanguage(string $lang): bool {
        return $this->directory->hasDirectory($lang);
    }
}
<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\I18n;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/25
 * Time: 17:15
 */
use Zodream\Disk\Directory;
use Zodream\Infrastructure\Base\MagicObject;
use Zodream\Infrastructure\Contracts\Translator;

abstract class I18n extends MagicObject implements Translator {

    const DEFAULT_LANGUAGE = 'zh-cn';

    protected string $fileName = 'zodream';

    protected string $locale = '';

    protected array $localeItems = [];

    /**
     * @var Directory
     */
    protected Directory $directory;

    public function __construct() {
        $configs = config('i18n', [
            'directory' => 'data/languages',
            'languages' => ['zh-cn', 'en'],
        ]);
        $this->localeItems = $configs['languages'];
        $this->setDirectory($configs['directory']);
//        $this->setLocale((string)$configs['locale']);
//        $this->reset();
    }

    public function isLoaded(): bool {
        return !empty($this->locale);
    }

    public function load(): void {
        if ($this->isLoaded()) {
            return;
        }
        $this->setLocale(config('app.locale', static::DEFAULT_LANGUAGE));
    }

    /**
     * SET LANGUAGE DIRECTORY
     * @param string|Directory $directory
     * @return $this
     */
    public function setDirectory(Directory|string $directory): static {
        if (!$directory instanceof Directory) {
            $directory = app_path()->childDirectory($directory);
        }
        $this->directory = $directory;
        return $this;
    }

    /**
     * SET FILE NAME
     * @param string $arg
     * @return $this
     */
    public function setFileName(string $arg): static {
        $this->fileName = $arg;
        return $this;
    }

    /**
     * 设置应用程序语言包
     * @param string $locale
     * @return $this
     */
    public function setLocale(string $locale = ''): static {
        if (empty($locale)) {
            $locale = $this->browserLanguage();
        }
        $this->locale = $this->formatLanguage($locale);
        $this->reset();
        return $this;
    }

    /**
     * 转换同一的语言标识
     * @param string $language
     * @return string
     */
    protected function formatLanguage(string $language): string {
        return strtolower($language);
    }

    protected function browserLanguage(): string {
        $language = request()->server('HTTP_ACCEPT_LANGUAGE', 'ZH-CN');
        if (empty($language) || !preg_match('/[\w-]+/', $language, $match)) {
            return self::DEFAULT_LANGUAGE;
        }
        if (strpos($match[0], '-Hans') > 0) {
            return self::DEFAULT_LANGUAGE;
        }
        return $match[0];
    }

    /**
     * 获取语言类型
     *
     * @return string 返回语言,
     */
    public function getLocale(): string {
        $this->load();
        return $this->locale;
    }

    public function isLocale(string $locale): bool {
        return empty($locale) || in_array($locale, $this->localeItems);
    }

    public function translate(mixed $message, array $param = [], ?string $name = null): mixed {
        $this->resetFileIfNotEmpty($name);
        return null;
    }

    protected function resetFileIfNotEmpty(?string $name): void {
        $this->load();
        if (!is_null($name) && $name !== $this->fileName) {
            $this->fileName = $name;
            $this->reset();
        }
    }

    public function format(mixed $message, array $param = []): mixed {
        if ($param === []) {
            return $message;
        }
        $args = [];
        foreach ($param as $key => $item) {
            $args['{'.$key.'}'] = $item;
        }
        // 替换
        return strtr($message, $args);
    }

    /**
     * 修改源
     */
    abstract public function reset(): void;

}
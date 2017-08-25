<?php
namespace Zodream\Domain\View;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/3
 * Time: 9:48
 */
use LogicException;
use Zodream\Infrastructure\Traits\ConfigTrait;
use Zodream\Service\Factory;
use Zodream\Service\Routing\Url;
use Zodream\Infrastructure\Caching\FileCache;
use Zodream\Infrastructure\Disk\Directory;
use Zodream\Infrastructure\Disk\File;
use Zodream\Infrastructure\Interfaces\EngineObject;
use Zodream\Infrastructure\Error\FileException;
use Zodream\Infrastructure\Support\Html;
use Zodream\Infrastructure\Base\MagicObject;
use Zodream\Infrastructure\ObjectExpand\ArrayExpand;

class ViewFactory extends MagicObject {

    protected $configKey = 'view';
    protected $configs = array();
    use ConfigTrait;


    /**
     * @var Directory
     */
    protected $directory;

    /**
     * @var EngineObject
     */
    protected $engine;

    /**
     * @var FileCache
     */
    protected $cache;

    protected $assetsDirectory;

    public $metaTags = [];

    public $linkTags = [];

    public $js = [];

    public $jsFiles = [];

    public $cssFiles = [];

    public $css = [];

    protected $sections = [];
    
    public function __construct() {
        $this->loadConfigs([
            'driver' => null,
            'directory' => 'UserInterface/'.APP_MODULE,
            'suffix' => '.php',
            'assets' => '/'
        ]);
        if (class_exists($this->configs['driver'])) {
            $class = $this->configs['driver'];
            $this->engine = new $class($this);
        }
        $this->setAssetsDirectory($this->configs['assets']);
        $this->cache = new FileCache();
        $this->setDirectory($this->configs['directory']);
        $this->set('__zd', $this);
    }

    public function setAssetsDirectory($directory) {
        $this->assetsDirectory = '/'.trim($directory, '/');
        if ($this->assetsDirectory != '/') {
            $this->assetsDirectory .= '/';
        }
        return $this;
    }

    /**
     * GET ASSET FILE
     * @param string $file
     * @return string
     */
    public function getAssetFile($file) {
        if (strpos($file, '/') === 0
            || strpos($file, '//') !== false) {
            return $file;
        }
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (strpos($file, '@') === 0 && ($ext == 'js' || $ext == 'css')) {
            $file = $ext.'/'. substr($file, 1);
        }
        return $this->assetsDirectory.$file;
    }
    
    public function setDirectory($directory) {
        if (!$directory instanceof Directory) {
            $directory = Factory::root()->childDirectory($directory);
        }
        $this->directory = $directory;
        return $this;
    }

    /**
     * MAKE VIEW
     * @param string|File $file
     * @return View
     * @throws FileException
     * @throws \Exception
     */
    public function make($file) {
        if (!$file instanceof File) {
            $file = $this->directory->childFile($file.$this->configs['suffix']);
        }
        if (!$file->exist()) {
            throw new FileException($file);
        }
        if (!$this->engine instanceof EngineObject) {
            return new View($this, $file);
        }
        /** IF HAS ENGINE*/
        $cacheFile = $this->cache->getCacheFile(sha1($file->getName()).'.php');
        if (!$cacheFile->exist() || $cacheFile->modifyTime() < $file->modifyTime()) {
            $this->engine->compile($file, $cacheFile);
        }
        return new View($this, $cacheFile);
    }

    /**
     * GET HTML
     * @param string|File $file
     * @param array $data
     * @param callable $callback
     * @return string
     * @throws FileException
     * @throws \Exception
     */
    public function render($file, array $data = array(), callable $callback = null) {
        return $this->set($data)->make($file)
            ->render($callback);
    }

    /**
     * @param string $content
     * @param array $options
     * @param null $key
     */
    public function registerMetaTag($content, $options = array(), $key = null) {
        if ($key === null) {
            $this->metaTags[] = Html::meta($content, $options);
        } else {
            $this->metaTags[$key] = Html::meta($content, $options);
        }
    }

    public function registerLinkTag($url, $options = array(), $key = null) {
        if ($key === null) {
            $this->linkTags[] = Html::link($url, $options);
        } else {
            $this->linkTags[$key] = Html::link($url, $options);
        }
        return $this;
    }

    public function registerCss($css, $key = null) {
        $key = $key ?: md5($css);
        $this->css[$key] = Html::style($css);
    }

    public function registerCssFile($url, $options = array(), $key = null) {
        $key = $key ?: $url;
        $options['rel'] = 'stylesheet';
        $this->cssFiles[$key] = Html::link($this->getAssetFile($url), $options);
        return $this;
    }

    public function registerJs($js, $position = View::HTML_FOOT, $key = null) {
        $key = $key ?: md5($js);
        $this->js[$position][$key] = $js;
        return $this;
    }

    public function registerJsFile($url, $options = [], $key = null) {
        $key = $key ?: $url;
        $position = ArrayExpand::remove($options, 'position', View::HTML_FOOT);
        $options['src'] = Url::to($this->getAssetFile($url));
        $this->jsFiles[$position][$key] = Html::script(null, $options);
        return $this;
    }

    /**
     * Start a new section block.
     * @param  string $name
     * @throws LogicException
     */
    public function start($name) {
        if ($name === 'content') {
            throw new LogicException(
                'The section name "content" is reserved.'
            );
        }
        $this->sections[$name] = '';
        ob_start();
    }

    /**
     * Stop the current section block.
     */
    public function stop() {
        if (empty($this->sections)) {
            throw new LogicException(
                'You must start a section before you can stop it.'
            );
        }
        end($this->sections);
        $this->sections[key($this->sections)] = ob_get_clean();
    }
    /**
     * Returns the content for a section block.
     * @param  string      $name    Section name
     * @param  string      $default Default section content
     * @return string|null
     */
    public function section($name, $default = null) {
        if (!isset($this->sections[$name])) {
            return $default;
        }
        return $this->sections[$name];
    }

    public function header() {
        $lines = [];
        if (!empty($this->metaTags)) {
            $lines[] = implode("\n", $this->metaTags);
        }

        if (!empty($this->linkTags)) {
            $lines[] = implode("\n", $this->linkTags);
        }
        if (!empty($this->cssFiles)) {
            $lines[] = implode("\n", $this->cssFiles);
        }
        if (!empty($this->css)) {
            $lines[] = implode("\n", $this->css);
        }
        if (!empty($this->jsFiles[View::HTML_HEAD])) {
            $lines[] = implode("\n", $this->jsFiles[View::HTML_HEAD]);
        }
        if (!empty($this->js[View::HTML_HEAD])) {
            $lines[] = Html::script(implode("\n", $this->js[View::HTML_HEAD]), ['type' => 'text/javascript']);
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    public function footer() {
        $lines = [];
        if (!empty($this->jsFiles[View::HTML_FOOT])) {
            $lines[] = implode("\n", $this->jsFiles[View::HTML_FOOT]);
        }
        if (!empty($this->js[View::HTML_FOOT])) {
            $lines[] = Html::script(implode("\n", $this->js[View::HTML_FOOT]), ['type' => 'text/javascript']);
        }
        if (!empty($this->js[View::JQUERY_READY])) {
            $js = "jQuery(document).ready(function () {\n" . implode("\n", $this->js[View::JQUERY_READY]) . "\n});";
            $lines[] = Html::script($js, ['type' => 'text/javascript']);
        }
        if (!empty($this->js[View::JQUERY_LOAD])) {
            $js = "jQuery(window).load(function () {\n" . implode("\n", $this->js[View::JQUERY_LOAD]) . "\n});";
            $lines[] = Html::script($js, ['type' => 'text/javascript']);
        }

        return empty($lines) ? '' : implode("\n", $lines);
    }

    public function clear() {
        parent::clear();
        $this->metaTags = [];
        $this->linkTags = [];
        $this->css = [];
        $this->cssFiles = [];
        $this->js = [];
        $this->jsFiles = [];
        $this->sections = [];
    }
}
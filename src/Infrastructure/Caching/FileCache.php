<?php 
namespace Zodream\Infrastructure\Caching;
/**
* 文件缓存类
* 
* @author Jason
*/
use Zodream\Disk\Directory;
use Zodream\Disk\File;
use Zodream\Infrastructure\Contracts\Cache as CacheInterface;

class FileCache extends Cache {

    const STORE_PREFIX = 'store_';

    /**
     * @var Directory
     */
	protected $directory;

    protected array $configs = [
        'directory' => 'data/cache/',
        'extension' => '.cache',
        'gc' => 10,
        'serializer' => null,
        'keyPrefix' => ''
    ];


    public function __construct() {
        $this->loadConfigs();
        $this->setDirectory($this->configs['directory']);
    }

    /**
     * 切换到子缓存区
     * @param $store
     * @return FileCache
     */
    public function store(string $store): CacheInterface {
        $newCache = clone $this;
        if (!empty($store)) {
            $newCache->setDirectory($this->directory->childDirectory(static::STORE_PREFIX.$store));
        }
        return $newCache;
    }

    public function setDirectory($directory) {
        if (!$directory instanceof Directory) {
            $directory = app_path()->childDirectory($directory);
        }
        $this->directory = $directory;
        if (!$directory->exist()) {
            $directory->create();
        }
        return $this;
    }

    protected function getValue($key) {
		$cacheFile = $this->getCacheFile($key);
		if (!$cacheFile->exist()) {
		    return false;
        }
        if ($cacheFile->modifyTime() < time()) {
		    $cacheFile->delete();
		    return false;
        }
        $fp = @fopen($cacheFile, 'r');
        if ($fp !== false) {
            @flock($fp, LOCK_SH);
            $cacheValue = @stream_get_contents($fp);
            @flock($fp, LOCK_UN);
            @fclose($fp);
            return $cacheValue;
        }
        return false;
	}
	
	protected function setValue($key, $value, $duration) {
		$this->gc();
		$cacheFile = $this->getCacheFile($key);
		if ($cacheFile->write($value, LOCK_EX) !== false) {
            if ($duration <= 0) {
                $duration = 31536000; // 1 year
            }
            return $cacheFile->touch($duration + time());
        }
        return null;
	}
	
	protected function addValue($key, $value, $duration) {
		$cacheFile = $this->getCacheFile($key);
        if ($cacheFile->modifyTime() > time()) {
            return false;
        }

        return $this->setValue($key, $value, $duration);
	}
	
	protected function hasValue($key) {
		$cacheFile = $this->getCacheFile($key);
        return $cacheFile->exist() && $cacheFile->modifyTime() > time();
	}
	
	protected function deleteValue($key) {
		$cacheFile = $this->getCacheFile($key);
        return $cacheFile->exist() && $cacheFile->delete();
	}
	
	protected function clearValue() {
		$this->gc(true, false);
		return true;
	}

    /**
     * @param string $key
     * @return File
     */
	public function getCacheFile($key) {
        $file = $this->directory->childFile($this->path($key));
        $file->getDirectory()->create();
		return $file;
	}

    protected function path($key) {
        $parts = array_slice(str_split($hash = sha1($key), 2), 0, 2);
        return implode('/', $parts).'/'.$hash.$this->configs['extension'];
    }
	
	public function gc(bool $force = false, bool $expiredOnly = true) {
        if ($force || mt_rand(0, 1000000) < $this->getGC()) {
            $this->gcRecursive($this->directory, $expiredOnly);
        }
    }
    
    protected function gcRecursive(Directory $directory, bool $expiredOnly) {
        foreach ($directory->children() as $item) {
            if ($item instanceof Directory &&
                str_starts_with($item->getName(), static::STORE_PREFIX)) {
                continue;
            }
            if (!$expiredOnly || ($item instanceof File
                    && $item->modifyTime() < time())) {
                $item->delete();
            }
        }
    }
}
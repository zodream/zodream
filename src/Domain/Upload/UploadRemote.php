<?php
declare(strict_types=1);
namespace Zodream\Domain\Upload;

use Zodream\Disk\FileSystem;
use Zodream\Http\Http;
use Zodream\Http\MIME;

class UploadRemote extends BaseUpload {

    protected string $url = '';
    protected string $mineType = '';

    public function __construct(string $url = '') {
        $this->load($url);
    }

    public function load(string $url = ''): void {
        if (empty($url)) {
            $this->setError('ERROR_HTTP_LINK');
            return;
        }
        $url = str_replace('&amp;', '&', htmlspecialchars($url));
        //http开头验证
        if (!str_starts_with($url, 'http')) {
            $this->setError('ERROR_HTTP_LINK');
            return;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            $this->setError('ERROR_HTTP_LINK');
            return;
        }
        // 此时提取出来的可能是 ip 也有可能是域名，先获取 ip
        $ip = gethostbyname($host);
        // 判断是否是私有 ip
        if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
            $this->setError('INVALID_IP');
            return;
        }
        $client = new Http($url);
        $headers = $client->getHeaders();
        //获取请求头并检测死链
        if ($client->getStatusCode() !== 200) {
            $this->setError('ERROR_DEAD_LINK');
            return;
        }
        $this->url = $url;
        //格式验证(扩展名验证和Content-Type验证)
        $type = FileSystem::getExtension($url, false);
        if (isset($headers['Content-Type'])) {
            $this->mineType = explode(';', $headers['Content-Type'], 2)[0];
        }
        $this->setType(empty($type) && isset($headers['Content-Type']) ?
            MIME::extension($headers['Content-Type']) : $type);

        if (preg_match('/[\/]([^\/]*)[\.]?[^\.\/]*$/', $url, $match)) {
            $this->name = $match[1];
        }
        if (isset($headers['Content-Disposition']) &&
            preg_match('/filename="?([^"]+)"?/', $headers['Content-Disposition'], $match)) {
            $this->name = $match[1];
            // $this->setType(FileSystem::getExtension($match[1], false));
        }
        $this->size = intval($headers['Content-Length'] ?? 0);
    }

    /**
     * 保存到指定路径
     * @return bool
     */
    public function save(): bool {
        if (!parent::save()) {
            return false;
        }
        if (empty($this->url)) {
            return false;
        }
        $client = new Http($this->url);
        $client->save($this->file);
        if (!$this->file->exist()) {
            $this->setError(
                __('ERROR_WRITE_CONTENT')
            );
            return false;
        }
        $this->size = $this->file->size();
        return true;
    }
}
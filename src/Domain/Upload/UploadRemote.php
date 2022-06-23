<?php
declare(strict_types=1);
namespace Zodream\Domain\Upload;


class UploadRemote extends BaseUpload {

    protected string $content = '';

    public function __construct(string $url = '') {
        $this->load($url);
    }

    public function load(string $url = '') {
        if (empty($url)) {
            $this->setError('ERROR_HTTP_LINK');
            return;
        }
        $imgUrl = str_replace('&amp;', '&', htmlspecialchars($url));

        //http开头验证
        if (!str_starts_with($imgUrl, 'http')) {
            $this->setError('ERROR_HTTP_LINK');
            return;
        }
        $host = parse_url($imgUrl, PHP_URL_HOST);
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

        //获取请求头并检测死链
        $heads = get_headers($imgUrl,  true);
        if (!(stristr($heads[0], '200') && stristr($heads[0], 'OK'))) {
            $this->setError('ERROR_DEAD_LINK');
            return;
        }
        //格式验证(扩展名验证和Content-Type验证)
        $this->setType(strtolower(strrchr($imgUrl, '.')));

        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(
            array('http' => array(
                'follow_location' => false // don't follow redirects
            ))
        );
        readfile($imgUrl, false, $context);
        $img = ob_get_contents();
        ob_end_clean();
        preg_match('/[\/]([^\/]*)[\.]?[^\.\/]*$/', $imgUrl, $m);
        $this->content = $img;
        $this->name = $m ? $m[1]:'';
        $this->size = strlen($this->content);
    }

    /**
     * 保存到指定路径
     * @return bool
     */
    public function save(): bool {
        if (!parent::save()) {
            return false;
        }
        if (!$this->file->write($this->content) ||
            !$this->file->exist()) { //移动失败
            $this->setError(
                __('ERROR_WRITE_CONTENT')
            );
            return false;
        }
        return true;
    }
}
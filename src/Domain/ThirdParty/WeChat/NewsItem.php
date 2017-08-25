<?php
namespace Zodream\Domain\ThirdParty\WeChat;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/8/20
 * Time: 11:13
 */
use Zodream\Infrastructure\Base\ZObject;

class NewsItem extends ZObject {
    protected $title;
    protected $thumb;
    protected $author;
    protected $digest;
    /**
     * @var bool
     */
    protected $showCover;
    protected $content;
    protected $url;

    /**
     * @var NewsItem[]
     */
    protected $articles = [];

    /**
     * UPDATE NEWS
     * @var string
     */
    protected $mediaId;
    protected $index = 0;

    public function setTitle($arg) {
        $this->title = $arg;
        return $this;
    }

    public function setThumb($arg) {
        $this->thumb = $arg;
        return $this;
    }

    public function setAuthor($arg) {
        $this->author = $arg;
        return $this;
    }

    public function setDigest($arg) {
        $this->digest = $arg;
        return $this;
    }

    public function setShowCover($arg) {
        $this->showCover = boolval($arg);
        return $this;
    }

    public function setUrl($arg) {
        $this->url = $arg;
        return $this;
    }

    public function setContent($arg) {
        $this->content = $arg;
        return $this;
    }

    public function setMediaId($arg) {
        $this->mediaId = $arg;
        return $this;
    }

    public function setIndex($arg) {
        $this->index = $arg;
        return $this;
    }

    public function setArticle($arg) {
        if (is_array($arg)) {
            $this->articles = $arg;
        } else {
            $this->articles[] = $arg;
        }
        return $this;
    }

    public function toArray() {
        if (empty($this->articles)) {
            return $this->getArticle();
        }
        $args = array_splice($this->articles, 0, 8);
        $data = [
            'articles' => array_map([$this, 'getArray'], $args)
        ];
        if (!empty($this->mediaId)) {
            $data['media_id'] = $this->mediaId;
            $data['index'] = $this->index;
        }
        return $data;
    }

    protected function getArray(NewsItem $item) {
        if (count($this->articles) > 1) {
            $this->setDigest(null);
        }
        return $item->toArray();
    }

    protected function getArticle() {
        return [
            'title' => $this->title,
            'thumb_media_id' => $this->thumb,
            'author' => $this->author,
            'digest' => $this->digest,
            'show_cover_pic' => intval($this->showCover),
            'content' => $this->content,
            'content_source_url' => $this->url
        ];
    }
}
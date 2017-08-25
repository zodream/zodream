<?php
namespace Zodream\Domain\ThirdParty\API;

/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/2
 * Time: 12:55
 */
use Zodream\Domain\ThirdParty\ThirdParty;
use Zodream\Infrastructure\ObjectExpand\JsonExpand;

class Microsoft extends ThirdParty  {

    protected $apiMap = array(
        'faceScore' => array(
            'http://kan.msxiaobing.com/Api/ImageAnalyze/Process?service=yanzhi',
            array(
                'MsgId',
                'CreateTime',
                'Content%5BimageUrl%5D'
            ),
            'post'
        ),
        'upload' => 'http://kan.msxiaobing.com/Api/Image/UploadBase64'
    );

    /**
     * 颜值测试
     * @param string $img base64_encode
     * @return array
     */
    public function faceScore($img) {
        /**
         * {"Host":"","Url":""}
         */
        $data = JsonExpand::decode($this->httpPost($this->apiMap['upload'], $img));
        $this->set(array(
            'MsgId' => time()."063",
            'CreateTime' => time(),
            'Content%5BimageUrl%5D' => $data['Host'].$data['Url']
        ));
        return $this->getJson('faceScore');
    }
}
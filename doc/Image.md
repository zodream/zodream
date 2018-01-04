# 图片处理目录

- [图片处理](#base)
- [二维码](#qrcode)
- [图片比较](#compare)
- [缩略图](#thumb)
- [加水印](#water)
- [验证码](#captcha)


## 命名空间
    Zodream\Image

<a name="base"></a>
## 图片处理

<a name="qrcode"></a>
## 二维码

依赖 [phpqrcode](http://phpqrcode.sourceforge.net/)

```PHP

QrCode::create('text'); // 创建二维码
QrCode::setLevel(0)->setSize(6)->create('');  // 设置容错率、尺寸
QrCode::create('')->addLogo(Image); // 添加logo
QrCode::email(''); // 邮件二维码
QrCode::geo(12, 12); // 地理位置二维码
QrCode::tel(tel); // 电话二维码
QrCode::sms(phone, ''); // 短信二维码
QrCode::wifi(''); // wifi二维码


```

<a name="compare"></a>
## 图片比较

```PHP

$image = new ImageCompare();

$image->compare(Image $image): bool;

```

<a name="thumb"></a>
## 缩略图

```PHP
$image = new ThumbImage();
$image->thumb('file');   //保存

```

<a name="water"></a>
## 加水印

```PHP
$image = new WaterMark();
$image->addText(''); //加文字
$image->addImage(''); //加水印图片
$image->addImageByDirection(''); // 根据九宫格添加图片
$image->addTextByDirection(''); // 根据九宫格添加文字

```

<a name="captcha"></a>
## 验证码

配置
```PHP
    'captcha' => [
        'characters' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', //随机因子
        'length' => 4,    //验证码长度
        'fontSize' => 30,   //指定字体大小
        'fontColor' => '',   //指定字体颜色, 可以是数组
        'fontFamily' => null,  //指定的字体
        'width' => 100,
        'height' => 30
    ]

```

使用

```PHP

$captcha = new Captcha();
$captcha->getCode(): string; //生成并获取随机码
$captcha->generate(1);        //生成图片

```


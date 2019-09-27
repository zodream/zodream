<?php

use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\I18n\PhpSource;
use Zodream\Infrastructure\Session\Session;
use Zodream\Infrastructure\Caching\FileCache;
use Zodream\Domain\Access\Auth;
use Zodream\Database\Engine\Pdo;
use Zodream\Infrastructure\Mailer\Mailer;
use Zodream\Infrastructure\Cookie;
use Zodream\Service\Config;
use Zodream\Infrastructure\Exceptions\Handler;

/**
 * 配置文件模板
 *
 * @author Jason
 * @time 2015-12-2
 */
$configs = [
	'app'    => [                           //网站信息
        'name'       => 'ZoDream',               //应用程序名称
		'title'      => 'ZoDream',
//		'host'       => 'localhost',        //主目录
		'model'      => 'Model',                     //后缀
		'form'       => 'Form',
		'controller' => 'Controller',
		'action'     => 'Action',
	],
	'session' => [
		'driver' => Session::class,
        'directory' => null
	],
	'cache' => [
		'driver' => FileCache::class,
		'auto' => false,
        'directory' => 'data/cache',
        'extension' => '.cache',
        'gc' => 10
	],
	'auth'   => [
		'driver' => Auth::class,        //用户判断
		'home'  => 'account'                             //用户登录主页
	],
	'route'  => [
		'default' => 'Home@index',                        //注册路由， (?<参数>值) 参数为方法接收的参数 值为正则表达式 或 :num :any
	],
    'formatter' => [
        'date' => 'Y-m-d',
        'datetime' => 'Y-m-d H:i:s',
        'timezone' => 'Etc/GMT-8'
    ],
    'exception' => [                            // 错误处理程序
        'driver' => Handler::class,  
    ],
	'db'     => [							//MYSQL数据库的信息
        'driver'   => Pdo::class,
        'type'     => 'mysql',
        'host'     => '127.0.0.1',                //服务器
        'port'     => '3306',						//端口
        'database' => 'test',					//数据库
        'user'     => 'root',						//账号
        'password' => 'root',						//密码
        'prefix'   => 'zd_',					//前缀
        'encoding' => 'utf8mb4',					//编码
        'allowCache' => true,                   //是否开启查询缓存
        'cacheLife' => 3600,                      //缓存时间
        'persistent' => false                   //使用持久化连接
	],
	'mail'   => [
		'driver'   => Mailer::class,
		'host'     => 'smtp.zodream.cn',
		'port'     => 25,
		'user'     => 'admin@zodream.cn',
		'name'     => 'ZoDream', //发送者名字
		'email'    => '',  //发送者邮箱
		'password' => ''
	],
	'verify' => [
		'length' => 4,
		'width' => 100,
		'height' => 30,
		'fontsize' => 20,
		'font' => 5
	],
	'upload' => [
		'maxsize'   => '',                  //最大上传大小 ，单位kb
		'allowtype' => 'mp3',				//允许上次类型，用‘；’分开
		'savepath'  => 'upload/'               //文件保存路径
	],
    'log' => [
        'name' => 'ZoDream',
        'level' => 'debug',
        'file' => sprintf('data/log/%s.log', date('Y-m-d'))
    ],
	'safe' => [
		'csrf' => false,						//是否使用csrf防止表单注入攻击
        //http://www.ruanyifeng.com/blog/2016/09/csp.html
        'csp' => [                              // 网页安全政策 Content-Security-Policy
            //'default-src \'self\'',             //script-src和object-src是必设的，除非设置了default-src。
            /*'script-src' => '',        //unsafe-inline unsafe-eval nonce hash 必须放在单引号里面。
            'style-src' => '',
            'img-src' => '',
            'media-src' => '',
            'font-src' => '',
            'object-src' => '',
            'child-src' => '',
            'frame-ancestor' => '',
            'connect-src' => '',
            'worker-src' => '',
            'manifest-src' => '',
            'report-uri' => '',*/
        ]
	],
	'alias'  => [
		'Cookie' => Cookie::class,
        'Request' => Request::class,
        'Auth' => Auth::class,
	],
	// 注册事件
	'event' => [
		'canAble' => true,            //是否启动注册事件
		'appRun' => [],
		'getRoute' => [],
		'runController' => [],
		'showView' => [],
		'response' => [],
		'download' => [],
		'executeSql' => [],
	],
    'i18n' => [
        'driver' => PhpSource::class,
        'directory' => 'data/languages',
        'language' => 'en'//'zh-cn',
    ],
    'view' => [                           //视图文件信息
        'directory' => 'UserInterface/'.app('app.module'),
        'suffix' => '.php',
        'asset_directory' => 'assets',
        'cache' => 'data/views',
        'assets' => [   //资源切换，正式环境自动使用cdn资源
            '@font-awesome.min.css' => 'https://use.fontawesome.com/releases/v5.11.2/css/all.css',
            '@animate.min.css' => 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.7.2/animate.min.css',
            '@bootstrap.min.css' => 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css',
            '@jquery.min.js' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js',
            '@bootstrap.min.js' => 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/js/bootstrap.min.js'
        ]
    ],

];

if (Config::isDebug()){
    $configs['modules'] = [   //生成模块
        'gzo' => 'Zodream\Module\Gzo',
        'debugger' => 'Zodream\Debugger' //调试模块
    ];
}
return $configs;
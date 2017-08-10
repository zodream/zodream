<?php
use Zodream\Infrastructure\Http\Request;
/**
 * 配置文件模板
 *
 * @author Jason
 * @time 2015-12-2
 */
$configs = array(
	'app'    => array(                           //网站信息
        'name'       => 'ZoDream',               //应用程序名称
		'title'      => 'ZoDream',
//		'host'       => 'localhost',        //主目录
		'model'      => 'Model',                     //后缀
		'form'       => 'Form',
		'controller' => 'Controller',
		'action'     => 'Action',
	),
	'session' => array(
		'driver' => Zodream\Infrastructure\Session\Session::class,
        'directory' => null
	),
	'cache' => array(
		'driver' => Zodream\Infrastructure\Caching\FileCache::class,
		'auto' => false,
        'directory' => 'data/cache',
        'extension' => '.cache',
        'gc' => 10
	),
	'auth'   => array(
		'driver' => Zodream\Domain\Access\Auth::class,        //用户判断
		'home'  => 'account'                             //用户登录主页
	),
	'route'  => array(
		'default' => 'Home@index',                        //注册路由， (?<参数>值) 参数为方法接收的参数 值为正则表达式 或 :num :any
	),
    'formatter' => [
        'date' => 'Y-m-d',
        'datetime' => 'Y-m-d H:i:s',
        'timezone' => 'Etc/GMT-8'
    ],
	'db'     => array(							//MYSQL数据库的信息
		'driver'   => Zodream\Infrastructure\Database\Pdo::class,
		'tyep'     => 'mysql',
		'host'     => '127.0.0.1',                //服务器
		'port'     => '3306',						//端口
		'database' => 'test',					//数据库
		'user'     => 'root',						//账号
		'password' => 'root',						//密码
		'prefix'   => 'zd_',					//前缀
		'encoding' => 'utf8',					//编码
		'allowCache' => true,                   //是否开启查询缓存
		'cacheLife' => 3600,                      //缓存时间
		'persistent' => false                   //使用持久化连接
	),
	'mail'   => array(
		'driver'   => Zodream\Infrastructure\Mailer\Mailer::class,
		'host'     => 'smtp.zodream.cn',
		'port'     => 25,
		'user'     => 'admin@zodream.cn',
		'name'     => 'ZoDream', //发送者名字
		'email'    => '',  //发送者邮箱
		'password' => ''
	),
	'verify' => array(
		'length' => 4,
		'width' => 100,
		'height' => 30,
		'fontsize' => 20,
		'font' => 5
	),
	'upload' => array(
		'maxsize'   => '',                  //最大上传大小 ，单位kb
		'allowtype' => 'mp3',				//允许上次类型，用‘；’分开
		'savepath'  => 'upload/'               //文件保存路径
	),
    'log' => array(
        'name' => 'ZoDream',
        'level' => 'debug',
        'file' => sprintf('data/log/%s.log', date('Y-m-d'))
    ),
	'safe' => array(
		'csrf' => false,						//是否使用csrf防止表单注入攻击
        //http://www.ruanyifeng.com/blog/2016/09/csp.html
        'csp' => [                              // 网页安全政策 Content-Security-Policy
            'default-src' => '\'self\'',             //script-src和object-src是必设的，除非设置了default-src。
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
	),
	'alias'  => array(
		'Config' => Zodream\Infrastructure\Config::class,
		'Requests' => Zodream\Infrastructure\Http\Request::class,
		'Cookie' => Zodream\Infrastructure\Cookie::class
	),
	// 注册事件
	'event' => array(
		'canAble' => true,            //是否启动注册事件
		'appRun' => array(),
		'getRoute' => array(),
		'runController' => array(),
		'showView' => array(),
		'response' => array(),
		'download' => array(),
		'executeSql' => array(),
	),
    'i18n' => array(
        'driver' => Zodream\Infrastructure\I18n\PhpSource::class,
        'directory' => 'data/languages',
        'language' => ''//'zh-cn',
    ),
);

if (defined('APP_MODULE')) {
	$configs['view'] = array(                           //视图文件信息
		'directory' => 'UserInterface/'.APP_MODULE,
		'suffix' => '.php',
	);
}
if (in_array(Request::ip(), ['unknown', '::1', '127.0.0.1'])){
    $configs['modules'] = array(   //模块
        'gzo' => 'Zodream\Module\Gzo'
    );
}
return $configs;
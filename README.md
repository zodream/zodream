# ZoDream

[![DUB](https://img.shields.io/dub/l/vibe-d.svg?maxAge=2592000)]()


## 当前版本 v2

## ZoDream 主程序

### 文件夹结构

> UserInterface 用户界面

> Service 服务 不包含任何业务逻辑，主要是定义要完成的任务，为界面提供功能，调用领域实现，

> Domain 领域 负责表达业务概念、业务状态信息、业务规则，包含领域模型

>> Access 用户信息处理，包括登录

>> Attack 安全

>> Controller 控制器基类

>> Debug 测试

>> Filter 数据过滤和验证

>>> Filters 过滤规则

>> Generate 代码生成

>>> Template 生成模板

>>> View 生成界面UI

>> Git git操作

>> Html 对HTML代码的封装，包括Bootstrap部件

>>> Bootstrap Bootstrap部件

>> Image 对图片的处理, 包括二维码、水印、缩略图、验证码、图片对比

>> Model ORM

>> Rss 订阅

>> Template 模板语言解析及处理

>> ThirdParty 第三方接口调用

>>> API 第三方实用接口，包括天气、快递、颜值

>>> OAuth 第三方登录接口

>>> SMS 短信接口

>>> WeChat 微信公众号

>> Upload 文件上传

>> View 视图处理

>>> Engine 视图处理引擎

> Infrastructure 基础设施 提供实现方法

>> Base 基类

>> Caching 缓存组件

>> Database 数据库查询组件

>>> Engine 数据库引擎

>>> Query 数据库查询

>>> Schema 数据库结构生成

>> Disk 文件系统管理

>> Interfaces 模型接口

>> Error 错误处理

>> Event 事件管理组件

>> Http 对 Http 访问的集成

>> I18n 国际化处理

>> Mailer 对 PHPmail 简化处理

>> ObjectExpand 基本的数据类型，及其拓展，包括 ENUM Xml Yaml 

>> Pipeline 管道

>> Request 对各种请求的处理

>> Security 加密

>> Session 对会话的拓展组件

>> Traits 其他部件

>> Url 网址操作

## 进度

维护

### 更新时间：2017-08-25 10:24:00
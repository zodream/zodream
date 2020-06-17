# ZoDream

[![DUB](https://img.shields.io/dub/l/vibe-d.svg?maxAge=2592000)]()


## 进度

    暂时未确定下一步功能调整方向
    当前主要任务是保持与php最新版本适应

## ZoDream 主程序

### 网址重写解析

以数字为分割符

如果参数大于1个则以无意义的数字为分割符

    /home/id/1       解析为 /home?id=1
    /home/0/id/1     解析为 /home?id=1
    /home/id/1/c/2   解析为 /home/id?c=2
    /home/0/id/1/c/2 解析为 /home?id=1&c=2

### 更新时间：2020-06-17 12:07:00
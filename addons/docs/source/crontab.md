---
title: 定时任务
type: docs
order: 2
---

## **定时任务**

在FastAdmin自带一套定时任务的功能，在配置上Crontab之前是不起作用的。

**操作步骤**
1. 在Crontab中新增一条定时任务的记录
使用命令`crontab -e`
然后追加上以下行

```
* * * * * /usr/bin/php /your-fastadmin-dir/public/index.php /index/autotask/crontab >> /var/log/fastadmin.`date +\%Y-\%m-\%d`.log 2>&1
```

2. 在后台`常规管理`->`定时任务`新增一条记录即可 
3. 执行成功后会在`runtime/log/crontab`目录中生成当天的日志文件

>`/index/autotask/crontab`这个地址只在CLI控制台起作用，浏览器访问是不起作用的。
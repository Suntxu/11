---
title: 模块
type: docs
order: 7
---

## 系统配置

在开发中经常会遇到一些配置信息可以在后台进行修改的功能，此时我们在系统配置中进行增改操作。

系统配置支持多种数据类型，下面依次做简单介绍

类型 | 介绍
--- | ---
字符 |     生成单行文本框
文本 |     生成多行文本框
数字 |     生成单行数字文本框
日期时间 |     生成文本框且自动生成日期时间选择器
列表 |     生成下拉列表框
列表(多) |     生成多选下拉列表框
图片 |     生成单图文本框且上传或选择单图，带图片预览
图片(多) |     生成多图文本框且可上传或选择多张图，带图片预览
文件 |     生成文本框且可上传或选择文件
文件(多) |     生成文本框且可上传或选择多个文件
复选 |     生成复选框
单选 |     生成单选框
数组 |     生成一维数组输入列表且可动态添加和排序

## 定时任务

在FastAdmin自带一套定时任务的功能，在配置上Crontab之前是不起作用的。

**操作步骤**
1. 在Crontab中新增一条定时任务的记录

```
* * * * * /usr/bin/php /your-fastadmin-dir/public/index.php /index/autotask/crontab >> /var/log/fastadmin.`date +\%Y-\%m-\%d`.log 2>&1
```

2. 在后台`常规管理`->`定时任务`新增一条记录即可 
3. 执行成功后会在`runtime/log/crontab`目录中生成当天的日志文件

>`/index/autotask/crontab`这个地址只在CLI控制台起作用，浏览器访问是不启作用的。

## 数据库管理

FastAdmin集成了一个简洁的数据库在线管理功能，可在线进行一些简单的数据库表优化或修复,查看表结构和数据的操作。也可以进行单条或多条SQL语句的查询。

为了避免表数据量过大导致浏览器卡顿的情况出现，FastAdmin在查看表数据和执行SELECT查询的时候做了Limit 100的限制，如果需要查询超过100条的数据，请手动在你的SQL语句后加上LIMIT限制。

如果是Update或Delete查询，则会返回影响的行数

>数据库管理权限过大，可任意操作数据库，建议只开放给超级管理员或数据库管理人员使用。

## 附件管理

附件管理可以管理后台上传的文件资源，也可以在此上传资源到服务器

在使用上传功能之前，建议先配置`/application/extra/upload.php`中的参数信息，否则上传功能和附件管理功能将无法正常使用。

在需要使用到上传的地方放置一个上传按钮，并且级这个按钮添加上`plupload`这个类即可

如果需要上传后将上传获得的地址填充到文本框，给对应的文本框加上一个属性，例如：

```
<input type="text" name="row[upyun]" id="c-upyun" class="form-control" cols="60" />
<button id="plupload-upyun" class="btn btn-danger plupload" data-input-id="c-upyun" data-after-upload="mycustomcallback"><i class="fa fa-upload"></i>上传</button>
```

其中`data-input-id`的值需要和文本框的ID的值对应起来，这样在上传成功以后FastAdmin会自动将文本填充值到文本框中去，
如果需要上传后进行回调处理自己的处理方法

比如上方的代码中有添加了属性`data-after-upload="mycustomcallback"`
此时你需要可以在你模块的JS文件中先注册一个回调函数，一定要注册在`Upload.api.custom`下，注册方法为：

```
require(["upload"], function (Upload) {
	Upload.api.custom.mycustomcallback = function (data) {
		console.log(data, "这里是自定义的回调方法");
	}
});
```

其中data就是从服务器返回的包含了上传URL的数据，此时你想要怎么处理URL都行。

## 微信管理

在使用微信管理之前，请先配置好`/application/extra/wechat.php`中的配置信息，其次在微信公众号开发者平台中配置好相应的回调地址，回调地址是：

```
http://www.yoursite.com/index/wechat/api
```
其次在公众号管理后台配置回调时，FastAdmin只有部署在外网环境下才可以正常配置！


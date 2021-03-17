---
title: 常见问题
type: docs
order: 9
---

如果你在使用FastAdmin的过程中发现任何问题,请到论坛发贴: http://forum.fastadmin.net

## 安装后提示控制器不存在:E或控制器不存在:N

出现这种情况一般是由于Web服务器的PATH_INFO未配置正确，导致服务器接收到了错误的PATH_INFO值，请检查你的PATH_INFO并修复后再重试

## FastAdmin的数据库SQL文件在哪里

FastAdmin在安装时会自动创建数据库和数据表,免除了你手动创建数据库和导入数据库的烦恼。
但很多时候我们需要构造自己的安装SQL，这就需要修改安装SQL文件。
FastAdmin的数据库安装文件保存在

[/application/admin/command/Install/fastadmin.sql](http://git.oschina.net/karson/fastadmin/raw/master/application/admin/command/Install/fastadmin.sql)


## 如何修改后台默认皮肤

为了进一步提升加载速度，后台默认启用了绿色主题的皮肤，如何修改其它皮肤呢？
1.找到`/public/assets/css/backend.css` 这个文件，默认是：
``` css
@import url("../css/bootstrap.min.css");
@import url("../css/fastadmin.min.css");
@import url("../css/skins/skin-green.css");
@import url("../css/iconfont.css");
@import url("../libs/font-awesome/css/font-awesome.min.css");
@import url("../libs/toastr/toastr.min.css");
@import url("../libs/layer/build/skin/default/layer.css");
@import url("../css/backend-func.css");
```

2.其中可以看到只加载了`skin-green.css`这个皮肤，如果需要启用其它皮肤可以在文件末尾追加
``` css
@import url("../css/skins/skin-颜色标识.css");
```
`颜色标识`总共有 `black/black-light/blue/blue-light/green/green-light/purple/purple-light/red/red-light/yellow/yellow-light`总12个颜色标识
如果需要一次性加载全部的皮肤样式，则把`skin-green`改为`_all-skins`即可

## php think install报不是内部或外部命令

这是由于php.exe文件所在目录未加入到PATH环境变量导致的

找到`php.exe`文件所在的目录，将该目录加入到系统PATH环境变量中后，重启即可解决


## php think install报command not found

这是由于在Linux环境下未找到php的脚本程序

有两种解决办法，首先尝试使用which php找到php所在的位置。
1. 找到php脚本程序所在的目录，加入到PATH环境变量中去，使用export PATH=$PATH:php脚本程序所在目录
2. 找到php脚本程序文件，使用ln -s php脚本程序文件 /usr/bin/php

## 安装后只能访问首页，其它页均报no input file specified

这是由于伪静态没有生效或错误导致的。

这种情况一般在Apache下伪静态不工作的情况下出现，
首先确保已经启用Apache的伪静态，确保目录已经配置好权限，如下面的Directory配置
``` apache
<VirtualHost *:80>
    DocumentRoot "/Users/Karson/Project/fastadmin/public"
    ServerName fa.com 
    ServerAlias fa.com *.fa.com
    <Directory "/Users/Karson/Project/fastadmin">
        AllowOverride All
        Options Indexes FollowSymLinks
        Require all granted
    </Directory>
</VirtualHost>
```

其次伪静态规则在Apache fastcgi模式下会导致No input file specified.
请修改public目录下的.htaccess文件

默认的

``` apache
RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
```

修改成
``` apache
RewriteRule ^(.*)$ index.php [L,E=PATH_INFO:$1]
```


## 安装后只能访问首页，其它页均报404 page not found

这是由于伪静态未配置或没有生效导致的。

这种情况一般在Nginx下未配置伪静态的情况下出现，建议将虚拟主机的root绑定至public目录

例如：

```
server {
        listen       80;
        server_name  www.fa.com *.fa.com;
        root   "C:/phpstudy/WWW/fastadmin/public";
        location / {
            index  index.html index.htm index.php;
            	#主要是这一段一定要确保存在
                if (!-e $request_filename) {
                    rewrite  ^(.*)$  /index.php/$1  last;
                    break;
                }
                #结束
            #autoindex  on;
        }
        location ~ \.php(.*)$ {
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
            fastcgi_split_path_info  ^((?U).+\.php)(/?.+)$;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            fastcgi_param  PATH_INFO  $fastcgi_path_info;
            fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_path_info;
            include        fastcgi_params;
        }
}
```

请将`C:/phpstudy/WWW`改成你服务器对应所在的目录

如果你使用的是 lnmp.org 的一键安装LNMP环境，
请查阅 https://lnmp.org/faq/lnmp-vhost-add-howto.html#rewrite 的伪静态配置


## composer install时无法下载package

这是由于composer默认配置是国外的源，如遇网络故障则会导致无法下载

1. 执行命令前先开启代理
2. 使用国内的镜像源，有以下两种启用方法

**#### 方法一： 修改 composer 的全局配置文件（推荐方式）**

打开命令行窗口并执行如下命令：

```
composer config -g repo.packagist composer https://packagist.phpcomposer.com
```

**#### 方法二： 修改当前项目的 composer.json 配置文件：**

打开命令行窗口，进入你的项目的根目录（也就是 composer.json 文件所在目录），执行如下命令：

```
composer config repo.packagist composer https://packagist.phpcomposer.com
```

感谢：https://pkg.phpcomposer.com/


## bower install时提示选择版本如何选择

这是由于不同插件对jQuery版本的要求不一致导致的，请选择较高版本即可


## 如何禁用后台登录的每日背景图切换

在FastAdmin后台登录时可以看到每日的背景图都是不一样的，如何想要固定一张背景图或禁用背景图该如何操作呢？


找到
```
/application/admin/views/index/login.html
```
修改
```
body {
    color:#999;
    background:url('http://img.infinitynewtab.com/wallpaper/{:date("Ymd")%4000}.jpg');
    background-size:cover;
}
```

修改其中的background地址即可


## 如何修改或禁用左侧菜单栏的角标

FastAdmin后台左侧菜单栏有彩色的小角标，这一般用于通知和提醒操作，在后台开发时是非常方便的一个小功能，如何修改和禁用它呢？
找到`/application/admin/controller/Index.php`中的index方法，其中有一段

```
$menulist = $this->auth->getSidebar([
	'dashboard'  => 'hot',
	'auth'       => ['new', 'red', 'badge'],
	'auth/admin' => 12,
	'auth/rule'  => 4,
	'general'    => ['18', 'purple'],
]);
```

数组的键名是对应的左侧菜单栏的相对链接
数组的键值是需要显示的文字或数字，可以传字符串或数组

1. 如果是字符串，则角标的颜色是按照'red', 'green', 'yellow', 'blue', 'teal', 'orange', 'purple'的方式进行循环的。
2. 如果是数组，这三个值分别表示：[显示的文字, 颜色，展现方式(badge或label)]

如果需要删除这个小角标，则可以直接到数组置为空即可

在这里仅仅是PHP端操作小角标的方式，在JS端同样可以进行相应的操作
在你的模块中可以调用

```
top.window.Backend.api.sidebar({
	'auth/admin':44
});
```

具体使用方法同PHP端相同
如何动态的在JS中移除一个小角标呢，采用以下的方法即可

```
top.window.Backend.api.sidebar({
	'auth/admin':0
});
```

## 在Windows下如何压缩打包JS和CSS

在FastAdmin中压缩打包JS和CSS文件需要NodeJS的支持
在Windows下需要手动配置Node的可执行文件,请修改`/application/admin/command/Min.php`中`$nodeExec`的值
如你的Node可执行文件是`C:/Program Files/nodejs/node.exe`，则请配置`$nodeExec = '"C:/Program Files/nodejs/node.exe"'`;


## 提示未知的数据格式或网络错误

很多时候都有可能遇到提示未知的数据格式或网络错误这个提示，产生这个错误的原因一般来说都是服务端报错，导致返回的数据不是JSON格式或直接未返回，如下图

![](http://cdn.forum.fastadmin.net/uploads/201706/02/0f650de53f0ee93ddfd658f731027d43)

准备工作：首先确保你的FA开启了调试模式`/application/config.php`中的`app_debug`置为`true`
两种定位错误的方法：
1.使用Chrome浏览器，打开开发者工具，选中Network(网络)选项卡,刷新一下页面或重新请求一次，定位到我们请求的URL，点击然后在Preview即可看到错误信息
2.直接查看`/runtime/log`目录下的错误日志

修复错误后再重试即可

FastAdmin建议运行在PHP5.5及以上版本，因此如果提示网络错误请检查你的PHP是否低于该版本
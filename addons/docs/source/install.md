---
title: 安装
type: docs
order: 2
---

## **环境要求**

~~~
PHP >= 5.5.0
Mysql >= 5.5.0
Apache 或 Nginx
PDO PHP Extension
MBstring PHP Extension
CURL PHP Extension
Node.js (可选,用于安装Bower和LESS,同时打包压缩也需要使用到)
Composer (可选,用于管理第三方扩展包)
Bower (可选,用于管理前端资源)
Less (可选,用于编辑less文件,如果你需要增改css样式，最好安装上)
~~~

## **源代码安装**

1. 下载FastAdmin完整包解压到你本地
	http://git.oschina.net/karson/fastadmin/attach_files
   还可以加QQ群([636393962](https://jq.qq.com/?_wv=1027&k=487PNBb)) 在群共享下载
2. 将你的虚拟主机绑定到`/yoursitepath/public`目录
3. 访问 http://www.yourwebsite.com/install.php 按指示进行安装

## **命令行安装** 

1. 克隆FastAdmin到你本地
	`git clone https://git.oschina.net/karson/fastadmin.git `
2. 进入目录
	`cd fastadmin `
3. 下载前端插件依赖包
	`bower install `
4. 下载PHP依赖包
	`composer install`
5. 一键创建数据库并导入数据
	`php think install`

## **常见问题**
1. 如果使用命令行安装则默认密码是`123456`
2. 提示`请先下载完整包覆盖后再安装`，说明你是直接从仓库下载的代码，请从附件或群共享中下载完整包覆盖后再进行安装
3. 执行`php think install`时出现`Access denied for user ...`，请确保数据库服务器、用户名、密码配置正确
4. 执行`php think install`时报不是内部或外部命令? 请将php.exe所在的目录路径加入到环境变量PATH中
5. 使用命令行安装时可能会由于你所处的网络环境导致资源下载不完整，请下载完整包覆盖后再尝试安装。

* * * * *
遇到问题到[论坛](http://forum.fastadmin.net) 或QQ群：[636393962](https://jq.qq.com/?_wv=1027&k=487PNBb) 反馈





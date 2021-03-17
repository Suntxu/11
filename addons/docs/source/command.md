---
title: 命令行
type: docs
order: 3
---
## 一键生成CRUD

FastAdmin可通过命令控制台快速的一键生成CRUD，操作非常简单，首先确保数据库配置正确。

1. 在数据库中创建一个fa_test数据表，编辑好表字段结构，并且一定写上`字段注释`和`表注释`，FastAdmin在生成CRUD时会根据字段属性、字段注释、表注释自动生成语言包和组件排版。
2. 打开控制台进入到FastAdmin根目录，也就是think文件所在的目录
3. 执行`php think crud -t test`即可一键生成test表的CRUD

>Windows系统一键生成CRUD请使用cmd命令控制台

>如果需要生成带目录层级的控制器则可以使用-c参数，例如`php think crud -t test -c mydir/test`，如此test控制器将在文件夹`mydir`目录下

>FastAdmin已经支持多表生成CRUD,请配置-r参数即可,更多参数请使用`php think crud --help`查看

常见问题：

1. 如果你的表带有下划级会自动生成带层级的控制器和视图，如果你不希望生成带层级的控制器和视图，请使用-c 参数，例如：`php think crud -t test_log -c testlog`将会生成testlog这个控制器，同理如果你的普通表想生成带层级的控制器则可以使用`php think crud -t test -c mydir/test`
2. FastAdmin自带一个fa_test表用于测试CRUD能支持的字段名称和类型，请直接使用`php think crud -t test`生成查看

使用范例：

![示例](http://wx1.sinaimg.cn/large/718e40a3gy1ff9k71b51yg20th0lje82.gif)

更多CRUD一键生成可使用的参数请使用`php think crud --help`查看

## 一键生成菜单

FastAdmin可通过命令控制台快速的一键生成后台的权限节点，同时后台的管理菜单也会同步改变，操作非常简单。首先确保已经将FastAdmin配置好，数据库连接正确。

1. 首先确保已经通过上一步的一键生成CRUD已经生成了test的CRUD
2. 打开控制台进入到FastAdmin根目录，也就是think文件所在的目录
3. 执行`php think menu -c test`即可生成Test控制器的权限节点
4. 如果想一键重置全部权限节点，可调用`php think menu -c all-controller`即可根据控制器一键重新生成后台的全部权限节点

>Windows系统一键生成菜单请使用cmd命令控制台

>如果你的控制器还有层级关系，比如你的test控制器位于mydir之下，则在生成菜单时使用`php think menu -c mydir/test`来生成

常见问题:
1. 在使用`php think menu`前确保你的控制器已经添加或通过`php think crud`生成好
2. 如果之前已经生成了菜单,需要再次生成,请登录后台手动删除之前生成的菜单或使用`php think menu -c 控制器名 -d 1`来删除

使用范例：

![示例](http://wx2.sinaimg.cn/large/718e40a3gy1ff9k644sesg20tl0lehdw.gif)

更多CRUD一键生成可使用的参数请使用`php think menu --help`查看



## 一键压缩打包JS、CSS文件

FastAdmin采用的是基于`RequireJS`的r.js进行JS和CSS文件的压缩打包，在进行下面的步骤之前，请先确保你的环境已经安装好Node环境。

1. 首先确认你`application/config.php`中app_debug的值，当为true的时候是采用的无压缩的JS和CSS，当为false时采用的是压缩版的JS和CSS。
2. 打开控制台进入到FastAdmin根目录，也就是think文件所在的目录
3. 执行`php think min -m all -r all`即可执行前后台的JS和CSS压缩打包， `-m all`表示前后模块均压缩 `-r all`表示CSS和JS均压缩
4. 参数可自由搭配，例如`php think min -m backend -r css`表示仅压缩后台的CSS文件

>Windows系统一键压缩打包JS、CSS文件请使用cmd命令控制台

JS和CSS文件压缩前和压缩后浏览器请求对比(请右键查看大图)：

![JS和CSS文件压缩前和压缩后浏览器请求对比](http://wx2.sinaimg.cn/large/718e40a3gy1ffjoe5t6dej21e010h7lu.jpg)

更多一键生成JS和CSS的参数请使用`php think min --help`查看


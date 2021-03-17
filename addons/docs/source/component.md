---
title: 组件
type: docs
order: 8
---

## 又拍云上传

FastAdmin支持将文件或图片直传至又拍云服务器而不需要通过本地服务器进行中转
在使用又拍云上传功能之前请先到又拍云注册一个账号并新增一个云储存服务，又拍云地址是https://console.upyun.com/register/?invite=SyAt3ehQZ
当创建好服务后获取到相应的配置信息
修改`/application/extra/upload.php` 文件，修改其中的配置

``` php
//上传配置
return [
    /**
     * 上传地址,默认是本地上传,如果需要使用又拍云则改为http://v0.api.upyun.com/yourbucketname
     */
    'uploadurl' => 'http://v0.api.upyun.com/yourbucketname',
    /**
     * 又拍云或本机的CDN地址
     */
    'cdnurl'    => 'http://yourbucketname.b0.upaiyun.com',
    /**
     * 上传成功后的通知地址
     */
    'notifyurl' => 'http://www.yoursite.com/upyun/notify',
    /**
     * 又拍云Bucket
     */
    'bucket'    => 'yourbucketname',
    /**
     * 生成的policy有效时间
     */
    'expire'    => '86400',
    /**
     * 又拍云formkey
     */
    'formkey'   => '',
    /**
     * 文件保存格式
     */
    'savekey'   => '/uploads/media/{year}/{mon}{day}/{filemd5}{.suffix}',
    /**
     * 最大可上传大小
     */
    'maxsize'   => '10mb',
    /**
     * 可上传的文件类型
     */
    'mimetype'  => '*',
    /**
     * 是否支持批量上传
     */
    'multiple'  => true,
    /**
     * 又拍云操作员用户名
     */
    'username'  => '',
    /**
     * 又拍云操作员密码
     */
    'password'  => '',
];
```

配置成功后即可在后台直接上传资源到又拍云了。
如果在配置了又拍云的基础上又想启用本地上传该如何操作呢？
给按钮添加一个属性`data-url="ajax/upload"`即可单独使用本地上传


## 第三方登录

FastAdmin自带一套第三方登录的扩展，包括QQ、微博、微信三种登录方式，在使用第三方登录前需要修改`/application/extra/third.php`的参数信息，其中回调地址可以为空，当回调地址为空时，FastAdmin会自动计算回调地址。其次需要在QQ、微博、微信的开放平台上配置好回调信息，例如FastAdmin默认生成QQ登录的回调地址是：

```
http://www.yoursite.com/index/user/third/action/callback/platform/qq
```
其它平台请按需设置

## 表单生成

FastAdmin封装了几个常用的方法，可以快速的生成表单元素

1. 生成下拉列表框build_select
~~~ php
<?php echo build_select('row[flag]', 'h,i,s', null, ['id'=>'c-flag','class'=>'form-control selectpicker','required'=>''])?>
~~~

2. 生成单选按钮组build_radios
~~~ php
<?php echo build_radios('row[enforce]', [1=>"是", 0=>"否"], 1);?>
~~~
 
3. 生成复选按钮组build_checkboxs
使用方法
~~~ php
<?php echo build_checkboxs('row[game]', [1=>"足球", 0=>"篮球"], 1);?>
~~~
  
## 阿里大鱼短信发送

使用阿里大鱼短信发送之前请预先到阿里大鱼上开通短信功能，生创建一个模板和签名
申请好配置信息后修改`/application/extra/service.php`中的alisms相应配置
使用方法：

~~~ php
$sms = new Alisms();
$sms->sign("FastAdmin")
    ->param(["key1"=>"值1", "key2"=>"值2"])
    ->template("模板ID")
    ->mobile("接收号码")
    ->send();
~~~

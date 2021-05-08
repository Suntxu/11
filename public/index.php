<?php



// +----------------------------------------------------------------------

// | ThinkPHP [ WE CAN DO IT JUST THINK ]

// +----------------------------------------------------------------------

// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.

// +----------------------------------------------------------------------

// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )

// +----------------------------------------------------------------------

// | Author: liu21st <liu21st@gmail.com>

// +----------------------------------------------------------------------
//测试测试
// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');

define('TOKEN_KEY','wwwhuaimicomcntop');

//官网

define('SPREAD_URL','http://www-dve.huaimi.com/');

//用户中心

define("WEBURL",'http://my-dve.huaimi.com/');

define("WEBNAME",'怀米网');
//第2个数据库
global $db2,$record_db,$reserve_db,$remodi_db;
$db2 = [
    // 数据库类型
    'type'            =>  'mysql',
    // 服务器地址
     'hostname'        => '39.99.203.192',
    // 数据库名
    'database'        => 'api_test_huaimi',
    // 用户名
    'username'        => 'api_test_huaimi',
    // 密码
    'password'        =>'3FmB6hpSezcNzj67',
    // 端口
    'hostport'        => '3306',
    // 数据库编码默认采用utf8
    'charset'         =>'utf8',
    // 数据库表前缀
    'prefix'          => 'domain_',
];

//历史删除记录查询
$record_db = [
    // 数据库类型
    'type'            =>  'mysql',
    // 服务器地址
     'hostname'        => '120.27.24.210',
    // 数据库名
    'database'        => 'yuding_back',
    // 用户名
    'username'        => 'yuding_back',
    // 密码
    'password'        =>'fRkc36C2WDYcDYy4',
    // 端口
    'hostport'        => '3306',
    // 数据库编码默认采用utf8
    'charset'         =>'utf8',
    // 数据库表前缀
    'prefix'          => 'yj_',

];

//预定信息采集库
$reserve_db = [
    // 数据库类型
    'type'            =>  'mysql',
    // 服务器地址
     'hostname'        => '39.101.209.254',
    // 数据库名
    'database'        => 'aliyuding_huaimi',
    // 用户名
    'username'        => 'aliyuding_huaimi',
    // 密码
    'password'        =>'mS6eWpNBTkfWd5mD',
    // 端口
    'hostport'        => '3306',
    // 数据库编码默认采用utf8
    'charset'         =>'utf8',
    // 数据库表前缀
    'prefix'          => 'yj_',

];

//记录库
$remodi_db = [
    // 数据库类型
    'type'            =>  'mysql',
    // 服务器地址
     'hostname'        => '39.100.202.21',
    // 数据库名
    'database'        => 'hmrecord',
    // 用户名
    'username'        => 'hmrecord',
    // 密码
    'password'        =>'eMfimFxbXD6GLzsf',
    // 端口
    'hostport'        => '3306',
    // 数据库编码默认采用utf8
    'charset'         =>'utf8',
    // 数据库表前缀
    'prefix'          => 'yj_',

];
/**

 * 表名前缀

 */
define('PREFIX','yj_');

define('OSS_ACCESS_KEY_ID','LTAI4Fk2EBRtXEGFT429B2hC');
define('OSS_ACCESS_KEY_Secret','L2AL0MedHQqTUgVlPc63wUxMeHP6tm');
define('OSS_POINT','http://oss-cn-qingdao.aliyuncs.com');//存储的地址
define('OSS_BUCKET_NAME','huaimi-img'); //图片类存储空间的名字
define("IMGURL","http://hm-img.huaimi.com/users/");//用户中心 图片访问地址
define("IMGURL_OPERATE","http://hm-img.huaimi.com/operate");//运营后台 图片访问地址
define('DOMAIN_RESERVE_REBATE',1); //预定得标返利比例 默认 1


//********** 阿里接口配置

define('ALI_Region_ID','cn-shanghai');

define('ALI_AccessKey_ID','LTAIFvR702GIR5rc');

define('ALI_Secret','41zVTPa7Ie25K0TyyV80d0pNNTsMwh');

//***********end

// 加载框架引导文件

require __DIR__ . '/../thinkphp/start.php';


//加载第三方插件自动加载文件

require __DIR__ . '/../vendor/autoload.php';


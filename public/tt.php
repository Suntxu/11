<?php

	define('APP_PATH', __DIR__ . '/../application/');
	
	define("BIND_MODULE", "worker/Offline");

	global $reserve_db;
	//历史删除记录查询
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



	// 加载框架引导文件
	require __DIR__ . '/../thinkphp/start.php';
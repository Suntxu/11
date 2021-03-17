---
title: 多语言
type: docs
order: 5
---

在FastAdmin中可以在任何位置(控制器、视图、JS)使用`__('语言标识');`调用语言包，如果语言标识不存在，则直接输出该语言标识

FastAdmin中的`__`函数和ThinkPHP中的`lang`函数在传参上有些许区别

比如
``` php
__('My name is %s', "FastAdmin");
```

将会返回

```
My name is FastAdmin
```

而如果采用ThinkPHP中的lang中的写法则是

``` php
lang('My name is %s', ["FastAdmin"]);
```

可以看到ThinkPHP中的第二个参数必须传入数组，而FastAdmin中的__则没有这个要求，其实在多个参数时FastAdmin会忽略掉第三个参数$lang
比如

``` php
__('This is %s,base on %s', 'FastAdmin', 'ThinkPHP5');
```

则会返回

```
This is FastAdmin,base on ThinkPHP5
```

而采用lang的写法则是

``` php
lang('This is %s,base on %s', ['FastAdmin', 'ThinkPHP5']);
```

因此如果要使第三个参数$lang生效，则只能将第二个参数传为数组或采用ThinkPHP中的lang函数

``` php
/**
 * 获取语言变量值
 * @param string    $name 语言变量名
 * @param array     $vars 动态变量值
 * @param string    $lang 语言
 * @return mixed
 */
function __($name, $vars = [], $lang = '')
{
	if (!is_array($vars))
	{
		$vars = func_get_args();
		array_shift($vars);
		$lang = '';
	}
	return Lang::get($name, $vars, $lang);
}
```

``` php
/**
 * 获取语言变量值
 * @param string    $name 语言变量名
 * @param array     $vars 动态变量值
 * @param string    $lang 语言
 * @return mixed
 */
function lang($name, $vars = [], $lang = '')
{
	return Lang::get($name, $vars, $lang);
}
```

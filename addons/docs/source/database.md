---
title: 数据库
type: docs
order: 6
---

这里提供的是数据库表字段规则在你新增表时使用，当按如下的规则进行字段命名时
使用`php think crud -t 表名`生成CRUD时会自动生成对应的HTML元素和组件 

## 根据字段类型 

类型 | 备注 | 类型说明
---|---|---
int | 整形 | 自动生成type为number的文本框，步长为1
enum | 枚举型 | 自动生成单选下拉列表框
set | set型 | 自动生成多选下拉列表框
float | 浮点型 | 自动生成type为number的文本框，步长根据小数点位数生成
text | 文本型 | 自动生成textarea文本框
datetime | 日期时间 | 自动生成日期时间的组件
date | 日期型 | 自动生成日期型的组件
timestamp | 时间戳 | 自动生成日期时间的组件

## 特殊字段 

字段 | 字段名称 | 字段类型 | 字段说明
---|---|---|---
category_id | 分类ID | int | 将生成选择分类的下拉框,分类类型根据去掉前缀的表名，单选
category_ids | 多选分类ID | varchar | 将生成选择分类的下拉框,分类类型根据去掉前缀的表名，多选
weigh | 权重 | int | 后台的排序字段，如果存在该字段将出现排序按钮，可上下拖动进行排序
createtime | 创建时间 | int | 记录添加时间字段,不需要手动维护
updatetime | 更新时间 | int | 记录更新时间的字段,不需要手动维护


## 以特殊字符结尾的规则 

结尾字符 | 示例 | 类型要求 | 字段说明
---|---|---|---
time | refreshtime | int | 识别为日期时间型数据，自动创建选择时间的组件
image | smallimage | varchar | 识别为图片文件，自动生成可上传图片的组件,单图
images | smallimages | varchar | 识别为图片文件，自动生成可上传图片的组件,多图
file | attachfile | varchar | 识别为普通文件，自动生成可上传文件的组件,单文件
files | attachfiles | varchar | 识别为普通文件，自动生成可上传文件的组件,多文件
avatar | miniavatar | varchar | 识别为头像，自动生成可上传图片的组件,单图
avatars | miniavatars | varchar | 识别为头像，自动生成可上传图片的组件,多图
content | maincontent | text | 识别为内容，自动生成带编辑器的组件
_id | user_id | int/varchar | 识别为关联字段，自动生成可自动完成的文本框，单选
_ids | user_ids | varchar | 识别为关联字段，自动生成可自动完成的文本框，多选
list | timelist | enum | 识别为列表字段，自动生成单选下拉列表
list | timelist | set | 识别为列表字段，自动生成多选下拉列表
data | hobbydata | enum | 识别为选项字段，自动生成单选框
data | hobbydata | set | 识别为选项字段，自动生成复选框
   
温馨提示：以list或data结尾的字段必须搭配enum或set类型才起作用 


## 注释说明

字段 | 注释内容 | 字段类型 | 字段说明
---|---|---|---
status | 状态 | int | 将生成普通语言包和普通文本框
status | 状态 | int | 将生成普通语言包和普通文本框
status | 状态 | enum(‘0’,’1’,’2’) | 将生成普通语言包和单选下拉列表
status | 状态:0=隐藏,1=正常,2=推荐 | enum(‘0’,’1’,’2’) | 将生成多个语言包和单选下拉列表，且列表中的值显示为对应的文字
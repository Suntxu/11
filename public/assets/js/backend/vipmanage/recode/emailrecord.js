define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/emailrecord/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({  
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'l.id',
                orderName:'desc',
                columns: [
                    [
                        {field: 'uid', title: '用户名',},
                        {field: 'l.email', title: '邮箱',},
                        {field: 'l.code', title: '验证码'},
                        {field: 'l.type', title: '类型',formatter: Table.api.formatter.status,searchList: {0:'找回密码',1:'重置安全码',2:'转回原注册商',3:'重置手机号',4:'注销账号'}},
                        {field: 'l.time', title: '发送时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'uip', operate:false, title: 'IP地址',formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'uip',fieldname:'wd',tit:'Ip归属地查询',},
                    ] 
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});




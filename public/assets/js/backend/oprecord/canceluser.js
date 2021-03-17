define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'oprecord/canceluser/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortOrder:'desc',
                sortName:'c.id',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'group', title: '用户名',operate:'LIKE',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/setuser/index',fieldvaleu:'userid',fieldname:'id',tit:'用户设置',},
                        {field: 'c.status', title: '审核状态',formatter: Table.api.formatter.status,searchList:{1:'注销失败',2:'注销成功'},notit:true},
                        {field: 'c.time', title: '提交时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'c.endtime', title: '审核时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'msg', title: '备注',operate:false},
                        { field: 'ip', title: 'IP',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'ip',fieldname:'wd',tit:'Ip归属地查询',},
                        {field: 'a.nickname', title: '操作人',},
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



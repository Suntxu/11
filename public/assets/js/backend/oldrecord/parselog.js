define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'oldrecord/parselog/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'newstime',
                pageSize: 25,
                exportDataType:'all',
                escape: false, //转义空格
                pageList: [10, 25, 50,100,200, 'All'],
                columns: [
                    [
                        {field: 'uid', title: '用户名',},
                        {field: 'tit', title: '域名',operate:'TEXT'},
                        {field: 'remark', title: '解析备注',operate:false},
                        // {field: 'r.remark', title: '解析类型', operate:'LIKE', formatter: Table.api.formatter.status,searchList: {'新增':'新增','修改':'修改','删除':'删除'},},
                        {field: 'newstime', title: '解析时间',operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'uip', title: 'IP地址',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'r.uip',fieldname:'wd',tit:'Ip归属地查询',},
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




define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'total/expdomain/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'r.del_time',
                orderName:'desc',
                columns: [
                    [   
                        { field: 'uid', title: '用户名',},
                        { field: 'r.tit', title: '域名',operate:'TEXT'},
                        { field: 'group', title: '后缀',formatter: Table.api.formatter.status,searchList: $.getJSON('domain/manage/getDomainHz'),},
                        { field: 'zcsj', title: '注册时间',addclass: 'datetimerange',sortable:true,operate: 'RANGE',formatter: Table.api.formatter.datetime},
                        { field: 'dqsj', title: '到期时间',addclass: 'datetimerange',sortable:true,operate: 'RANGE',formatter: Table.api.formatter.datetime},
                        { field: 'r.del_time', title: '删除时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                    ]
                ],
                
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        // edit: function () {
        //     Controller.api.bindevent();
        // },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'oprecord/multireserve/index',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'r.id',
                orderName:'desc',
                columns: [
                    [
                        { field: 'r.tit', title: '域名', },
                        { field: 'del_time', title: '删除时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'group', title: '注册商',searchList: $.getJSON('domain/reserve/multop/getZcs')},
                        { field: 'r.status', title: '操作状态',formatter: Table.api.formatter.status,searchList: {1:'提交成功',2:'预定成功',3:'预定失败',4:'提交失败'},},
                        { field: 'r.create_time', title: '操作时间',addclass:'datetimerange',sortable: true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        { field: 'a.nickname', title: '操作人', },
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


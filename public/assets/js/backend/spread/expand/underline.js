define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'spread/expand/underline/index',
                    add_url: 'spread/expand/underline/add',
                    edit_url: 'spread/expand/underline/edit',
                    del_url: 'user/expand/underline/del',
                    multi_url: 'spread/expand/underline/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { checkbox: true },
                        { field: 'id', title: 'ID', sortable: true },
                        { field: 'uid', title: '账号', operate: 'LIKE' },
                        { field: 'money1', title: '可用余额', operate: 'BETWEEN', sortable: true },
                        { field: 'sj', title: '注册时间', sortable: true, addclass: 'datetimerange', operate: 'RANGE' },
                        { field: 'uip', title: '注册IP',  },
                        { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ]
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
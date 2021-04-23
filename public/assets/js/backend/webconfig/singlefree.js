define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                // showFooter: true,
                extend: {
                    index_url: 'webconfig/singlefree/index',
                    add_url: 'webconfig/singlefree/add',
                    edit_url: 'webconfig/singlefree/edit',
                    multi_url: 'webconfig/regsuffix',
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                escape:false,
                sortName: 'add_time',
                columns: [
                    [
                        { checkbox: true,},
                        { field: 'uid', title: '用户',},
                        { field: 'free_money', title: '转回金额', operate:'BETWEEN' },
                        { field: 'start_time', title: '开始时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'over_time', title: '结束时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'username', title: '操作管理员'},
                        { field: 'add_time', title: '添加时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'r.status', title: '状态', formatter: Table.api.formatter.status,searchList: {1:'禁用',2:'正常'}},
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

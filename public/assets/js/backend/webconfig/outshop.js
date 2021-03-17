define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'webconfig/outshop/index',
                    add_url: 'webconfig/outshop/add',
                    edit_url: 'webconfig/outshop/edit',
                    del_url: 'webconfig/outshop/del', 
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                orderName: 'desc',
                escape: false,
                columns: [
                    [
                        { checkbox: true},
                        { field: 'shopid', title: '店铺id'},
                        { field: 'discount', title: '折扣率',sortable:true },
                        { field: 'type', title: '合作方',searchList:{0:'聚名'} },
                        { field: 'create_time', title: '创建时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
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
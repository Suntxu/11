define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: '/admin/domain/recycle/broker/index',
                    add_url: '/admin/domain/recycle/broker/add',
                    edit_url: '/admin/domain/recycle/broker/edit',
                    del_url: '/admin/domain/recycle/broker/del',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'id',
                orderName:'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'name', title:'昵称',},
                        {field: 'wx', title:'微信',},
                        {field: 'qq', title:'QQ',},
                        // {field: 'mot', title:'电话',},
                        {field: 'imgpath', title:'微信二维码', formatter: Table.api.formatter.image, operate: false},
                        {field: 'status', title: '状态',formatter: Table.api.formatter.status, searchList:{0:'正常',1:'禁用'}},
                        {field: 'create_time',title: '添加时间',addclass:'datetimerange',operate:'INT', formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'operate', title: __('Operate'), table: table,events: Table.api.events.operate,formatter: Table.api.formatter.operate},
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



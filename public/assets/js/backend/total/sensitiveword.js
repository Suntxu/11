define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: '/admin/total/sensitiveword/index',
                    add_url: '/admin/total/sensitiveword/add',
                    edit_url: '/admin/total/sensitiveword/edit',
                    del_url: '/admin/total/sensitiveword/del',
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
                escape:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'title', title:'敏感词',align:'left',operate:'LIKE'},
                        {field: 'type', title: '类型',searchList:{0:'页面搜索'}},
                        {field: 'status', title: '状态',searchList:{0:'开启',1:'关闭'}},
                        {field: 'create_time',title: '添加时间',addclass:'datetimerange',operate:'INT', formatter: Table.api.formatter.datetime,sortable:true,},
                        {field: 'operate', title: __('Operate'), table: table,events: Table.api.events.operate,formatter: Table.api.formatter.operate},
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



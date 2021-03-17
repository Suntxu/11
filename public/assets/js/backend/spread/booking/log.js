define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/booking/log/index',
                    edit_url: 'spread/booking/log/edit',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'a.id',
                sortOrder:'desc',
                columns: [
                    [
                        { field: 'a.id', title: 'ID', sortable: true,operate:false, },
                        { field: 'c.name', title: '用户名称',sortable:false,operate:false,},
                        { field: 'b.team_no', title: '团队编号',sortable: false,operate:false,},
                        { field: 'a.log', title: '日志备注',sortable: false,operate:"LIKE",},
                        { field: 'a.type', title: '日志类型',sortable: false,operate:false},
                        { field: 'a.admin_id', title: '审核用户ID',sortable: false,operate:false,},
                        { field: 'a.admin_name', title: '审核人名称',sortable:false ,operate:false,},
                        { field: 'a.created_at', title: '创建时间',  operate:'INT',sortable: true,addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
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
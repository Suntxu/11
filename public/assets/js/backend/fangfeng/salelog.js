define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'fangfeng/salelog/index',
                    add_url: 'fangfeng/salelog/add',
                    edit_url: 'fangfeng/salelog/edit',
                    del_url: 'fangfeng/salelog/del', 
                    table: 'user',
                }
            });
            var table = $("#table");
            var id = null;
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'sale_time',
                sortOrder:'desc',
                columns: [
                    [  
                        { checkbox: true,},
                        { field: 'username', title: '用户名'},
                        { field: 'qq', title: 'QQ号', },
                        { field: 'token', title: '密钥', },
                        { field: 'money', title: '售价',operate:'BETWEEN', footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                        { field: 'server_type', title: '服务类型', formatter: Table.api.formatter.status,searchList: {0:'包月',1:'包年'}},
                        { field: 'start_time', title: '服务开通时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'end_time', title: '服务结束时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'sale_time', title: '出售时间',operate: 'INT',addclass: 'datetimerange',formatter: Table.api.formatter.datetime,sortable:true},
                        { field: 'status', title: '状态', formatter: Table.api.formatter.status,searchList: {0:'启用',1:'禁用'}},
                        { field: 'remark', title: '备注',operate:false},
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
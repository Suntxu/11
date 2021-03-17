define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'oprecord/shift/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                OrderName:'desc',
                sortName:'b.id',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'bath', title: '批次',},
                        {field: 'audit', title: '审核状态',formatter: Table.api.formatter.status,notit:'true',searchList:{1:'任务执行成功',2:'审核失败',3:'任务执行中'}},
                        {field: 'b.reg_id', title: '目标注册商',formatter: Table.api.formatter.status,notit:'true',searchList:{66:'阿里云',67:'西数'},},
                        {field: 'b.api_id', title: '目标账号',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        {field: 'u.uid', title: '申请人',},
                        {field: 'b.email', title: '申请账号',},
                        {field: 'subdate', title: '提交时间',sortable:true,operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'finishdate', title: '审核时间',sortable:true,operate: 'INT', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'ad.nickname', title: '操作人',},
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


define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'oprecord/disuser/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'d.id',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'u.uid', title: '用户',},
                        {field: 'd.type', title: '类型',searchList: {0:'正常',1:'禁用'}},
                        {field: 'd.create_time', title: '操作时间',operate: 'INT', addclass: 'datetimerange',formatter: Table.api.formatter.datetime },
                        {field: 'dis_days', title: '禁用天数',operate:'BETWEEN',sortable:true},
                        {field: 'dis_time', title: '禁用到期时间',operate: 'INT', addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                        {field: 'remark', title: '原因',operate:false},
                        {field: 'a.nickname', title: '操作人',},
                    ]
                ],

            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
    };
    return Controller;
});

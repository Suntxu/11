define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/recode/dnsrecord/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'r.id',
                sortName:'r.id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [

                        {field: 'd.tit', title: '域名',operate: 'TEXT'},
                        {field: 'r.remark', title: 'DNS值'},
                        {field: 'u.uid', title: '用户'},
                        {field: 'r.status', title: '执行进度', formatter: Table.api.formatter.status,notit:true, searchList: {0:"执行中",1:"执行完成"}},
                        {field: 'dp.zcs', title: '注册商',searchList: $.getJSON('category/getcategory?type=api&xz=parent')},
                        {field: 'd.api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName')},
                        {field: 'd.TaskStatusCode', title: '任务状态', formatter: Table.api.formatter.status,notit:true, searchList: {0:"等待执行",1:"执行中",2:"执行成功",3:"执行失败"}},
                        {field: 'd.ErrorMsg', title: '错误原因'},
                        {field: 'r.createtime', title: '任务开始时间', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'd.CreateTime', title: '任务结束时间', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                    ]
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

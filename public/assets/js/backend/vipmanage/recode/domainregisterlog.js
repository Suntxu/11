define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'vipmanage/recode/domainregisterlog/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'r.id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [
                        {field: 'id', title: '主任务ID',operate: false},
                        {field: 'd.tit', title: '域名',operate: 'TEXT'},
                        {field: 'd.money', title: '单价',operate: 'BETWEEN',sortable:true},
                        {field: 'cos_price', title: '成本价',operate: false,sortable:true},
                        {field: 'u.uid', title: '用户'},
                        {field: 'r.status', title: '执行进度', formatter: Table.api.formatter.status,notit:true, searchList: {0:"执行中",1:"执行成功"}},
                        // {field: 'r.zcs', title: '注册商',searchList: $.getJSON('category/getcategory?type=api&xz=parent')},
                        {field: 'd.api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName')},
                        {field: 'd.hz', title: '后缀', searchList: $.getJSON('domain/manage/getDomainHz'),operate:'IN',addclass:'request_selectpicker',},
                        {field: 'd.TaskStatusCode', title: '任务状态', formatter: Table.api.formatter.status,notit:true, searchList: {0:"等待执行",1:"执行中",2:"执行成功",3:"执行失败",9:'已退款'}},
                        {field: 'd.ErrorMsg', title: '错误原因'},
                        {field: 'r.a_type', title: '注册类型', formatter: Table.api.formatter.status,notit:true, searchList: {0:"普通",1:"拼团",2:"限量",3:"注册包"}},
                        {field: 'r.createtime', title: '任务开始时间', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'd.CreateTime', title: '任务结束时间', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'r.uip', title: '注册IP'},
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

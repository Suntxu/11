define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'oprecord/withdraw/index',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'l.id',
                orderName:'desc',
                columns: [
                    [
                        { field: 'uid', title: '用户名称', },
                        { field: 'money', title: '提现金额',operate: false,sortable: true,},
                        { field: 'on', title: '流水信息',operate: false,sortable: true,formatter:Table.api.formatter.alink,url:'/admin/spread/flow',fieldvaleu:'id',fieldname:'id',tit:'交易订单',},
                        { field: 'l.status', title: '提取状态',formatter: Table.api.formatter.status,searchList: {'1':'提取成功','2':'提取失败'},},
                        { field: 'l.ctime', title: '申请时间',addclass:'datetimerange',sortable: true,operate: 'RANGE',formatter: Table.api.formatter.datetime},
                        { field: 'a.nickname', title: '操作人', },
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

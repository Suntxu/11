define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/elchee/distr/distribution/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'add_time',
                columns: [
                    [
                        { field: 'uid', title: '用户', },
                        { field: 'd.status', title: '审核状态', formatter: Table.api.formatter.status,searchList: {1:'审核失败',2:'审核成功'}},
                        { field: 'msg', title: '处理声明',operate:false},
                        { field: 'add_time', title : '申请时间',addclass:'datetimerange',sortable: true,operate: 'RANGE',formatter: Table.api.formatter.datetime}
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

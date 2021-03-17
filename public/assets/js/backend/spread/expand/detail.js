define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'spread/expand/detail/index',
                    add_url: 'spread/expand/detail/add',
                    edit_url: 'spread/expand/detail/edit',
                    del_url: 'user/spread/expand/detail/del',
                    multi_url: 'spread/expand/detail/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        { checkbox: true,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            } },
                        { field: 'id', title: '用户ID', sortable: true },
                        { field: 'uid', title: '用户名称',},
                        { field: 'sj', title: '注册时间',addclass:'datetimerange',operate: 'RANGE',},
                        { field: 'prevpay', title: '上月充值金额',operate:false },
                        { field: 'monpay', title: '本月充值金额',operate:false},
                        { field: 'allpay', title: '总金额',operate:false,tdurl:'总金额',formatter:Table.api.formatter.alink,url:'/admin/spread/expand/proindent/',fieldvaleu:'id',fieldname:'userid',tit:'推广订单',
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'username', title: '推广员工',operate:false},
                        // { field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
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
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'oprecord/recharge/index',
                    table: 'user',
                }
            });
            
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'd.id',
                orderName:'desc',
                escape:false,
                columns: [
                    [
                        { field: 'ddbh', title: '订单编号',operate:'LIKE',formatter: Table.api.formatter.search,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        { field: 'uid', title: '会员账号',},
                        { field: 'd.sj', title: '交易时间',addclass: 'datetimerange',sortable:true,operate: 'RANGE',},
                        { field: 'd.money1', title: '金额',sortable:true,operate: 'BETWEEN',
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '记录总金额：'+total_sum.toFixed(2);
                            }
                        },
                        { field: 'wxddbh', title: '交易号',},
                        { field: 'remark', title: '备注',operate:false},
                        { field: 'a.nickname', title: '操作人',},
                        
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

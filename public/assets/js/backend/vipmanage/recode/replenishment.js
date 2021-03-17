define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/replenishment/index',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'r.id',
                sortName: 'r.create_time',
                escape:false,
                columns: [
                    [
                        { field: 'd.ddbh', title: '订单编号',operate:'LIKE',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }},
                        { field: 'u.uid', title: '补单账号',},
                        { field: 'd.sj', title: '提交时间',operate:false},
                        { field: 'd.money1', title: '充值金额',sortable:true,operate: 'BETWEEN',
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return '补单总金额：'+total_sum.toFixed(2);
                            }
                        },
                        { field: 'bz', title: '交易方式',operate:'LIKE',formatter: Table.api.formatter.status,searchList: {'微信支付':'微信支付','支付宝':'支付宝','人工充值':'人工充值','财付通':'财付通','快钱':'快钱'}},
                        { field: 'a.username', title: '操作者',},
                        { field: 'r.create_time', title: '操作时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var gro = $("#ddbh").val();
                    if (gro != '')
                        filter['d.ddbh'] = gro;
                    params.filter = JSON.stringify(filter);
                    params.op = JSON.stringify(op);
                    return params;
                }
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

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'oprecord/tx/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'t.id',
                escape: false, //转义空格
                columns: [
                    [   
                        {field: 'u.uid', title: '提现会员',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/tx/edit',fieldvaleu:'id',fieldname:'id',tit:'提现审核',},
                        {field: 't.zt', title: '状态',formatter: Table.api.formatter.status,notit:'true',searchList: {'1':'提现成功', '3':'提现失败'},align:'left'},
                        {field: 't.type', title: '提现类型',searchList: {0:'普通提现',1:'注销提现'}},
                        {field: 't.txyh', title: '提现银行',},
                        {field: 't.money1', title: '提现金额',operate:'BETWEEN',footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }},
                        {field: 't.sj', title: '提现时间',operate: 'RANGE', addclass: 'datetimerange', },
                        {field: 'sm', title: '备注',operate:false},
                        {field: 'ip', title: 'IP',operate: false,formatter:Table.api.formatter.alink,url:'http://www.baidu.com/s',fieldvaleu:'ip',fieldname:'wd',tit:'Ip归属地查询',},
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

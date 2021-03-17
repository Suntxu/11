define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/tx/index',
                    edit_url: 'vipmanage/tx/edit',
                    table: 'user',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'t.sj',
                escape: false, //转义空格
                columns: [
                    [   
                        {field: 'u.uid', title: '提现会员',formatter:Table.api.formatter.alink,url:'/admin/vipmanage/recode/dinndan',fieldvaleu:'u.uid',fieldname:'uid',tit:'用户管理',},
                        {field: 't.type', title: '提现类型',searchList: {0:'普通提现',1:'注销提现'}},
                        {field: 't.zt', title: '状态',formatter: Table.api.formatter.status,notit:'true',searchList: {1:'提现成功',2:'用户撤销提现',3:'提现失败',4:'等待受理',5:'提现审核中'},align:'left'},
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
                        {field: 'op', title: '承诺信息',operate:false},
                        {field: 'operate', title: __('Operate'),table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate }
                    ]
                ],
                queryParams: function (params) {
                    var filter = JSON.parse(params.filter);
                    var op = JSON.parse(params.op);
                    //这里可以追加搜索条件
                    var id = $("#id").val();
                    if (id != '')
                        filter['t.id'] = id;
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



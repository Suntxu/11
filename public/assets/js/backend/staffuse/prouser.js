define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'staffuse/prouser/index',
                   
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
                      
                        { field: 'id', title: '用户ID', sortable: true,operate:false,footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                            }  },
                        { field: 'uid', title: '用户名称', operate: 'LIKE' },
                        { field: 'sj', title: '注册时间', addclass: 'datetimerange', sortable: true,operate: 'RANGE', },
                        { field: 'prevpay', title: '上月充值金额',operate:false },
                        { field: 'monpay', title: '本月充值金额',operate:false},
                        { field: 'allpay', title: '总金额',operate:false,tdurl:'总金额',formatter:Table.api.formatter.alinks,url:'/admin/staffuse/proorder/',fieldvaleu:['id',1],fieldname:['userid','ifok'],tit:'充值记录',
                            footerFormatter: function (data) {
                                var field = 'zje';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                            }
                        },
                        { field: 'chenn', title: '渠道名称',operate:false, },
                        { field: 'channel', title: '渠道名称', visible: false, formatter: Table.api.formatter.status, searchList: Table.api.getSelectDate('category/getSelectName') },
                        { field: 'zt', title: '状态', formatter: Table.api.formatter.status,searchList: {'1':'正常','3':'冻结','2':'邮箱未激活'},},
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
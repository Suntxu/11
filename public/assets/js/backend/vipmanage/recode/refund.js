define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                showFooter: true,
                extend: {
                    index_url: 'vipmanage/recode/refund/index',
                    table: 'user',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'r.id',
                orderName:'desc',
                escape: false, //转义空格
                columns: [
                    [   
                        { field:'oid',title:'操作ID',footerFormatter: function (data) {
                                return '统计：';//在第一列开头写上总计、统计之类
                        }},
                        { field: 'uid', title: '用户名',},
                        { field: 'tit', title: '域名',},
                        { field: 'money', title: '退款金额',sortable:true,operate: 'BETWEEN',footerFormatter: function (data) {
                                var field = 'num';
                                var total_sum = data.reduce(function (sum, row) {
                                    return parseFloat(row[field]);
                                }, 0);
                                return total_sum.toFixed(2);
                        }},
                        { field: 'atime', title: '域名注册时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},
                        {field: 'zcs', title: '注册商',searchList: $.getJSON('category/getcategory?type=api&xz=parent') },
                        {field: 'api_id', title: '接口商',searchList: $.getJSON('webconfig/regapi/getRegisterUserName'),},
                        { field: 'r.create_time', title: '记录时间',addclass: 'datetimerange',sortable:true,operate: 'INT',formatter: Table.api.formatter.datetime},

                    ]
                ],
                
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        // edit: function () {
        //     Controller.api.bindevent();
        // },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
